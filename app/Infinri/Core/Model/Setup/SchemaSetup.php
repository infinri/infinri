<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Setup;

use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Core\Helper\Logger;

/**
 * Schema Setup
 * 
 * Processes db_schema.xml files and creates/updates database tables
 * Like Magento's declarative schema system
 */
class SchemaSetup
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }
    
    /**
     * Get PDO connection
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->connection->getConnection();
    }
    
    /**
     * Process module's db_schema.xml file
     *
     * @param string $moduleName
     * @param string $schemaFile
     * @return array ['created' => int, 'updated' => int]
     */
    public function processModuleSchema(string $moduleName, string $schemaFile): array
    {
        // Debug: Echo directly so we can see it
        echo "\n  [SchemaSetup] Processing {$moduleName}\n";
        echo "  [SchemaSetup] File: {$schemaFile}\n";
        
        Logger::info("SchemaSetup: Processing schema for {$moduleName}", [
            'file' => $schemaFile
        ]);
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($schemaFile);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $errorMsg = "SchemaSetup: Failed to parse {$schemaFile}";
            foreach ($errors as $error) {
                $errorMsg .= "\n  - " . trim($error->message);
            }
            libxml_clear_errors();
            
            Logger::warning($errorMsg);
            file_put_contents(__DIR__ . '/../../../../var/schema_debug.log', 
                $errorMsg . "\n", 
                FILE_APPEND
            );
            return ['created' => 0, 'updated' => 0];
        }
        
        $created = 0;
        $updated = 0;
        
        // Process each table definition
        echo "  [SchemaSetup] Found " . count($xml->table) . " tables in XML\n";
        
        foreach ($xml->table as $tableNode) {
            $tableName = (string)$tableNode['name'];
            
            $exists = $this->tableExists($tableName);
            echo "  [SchemaSetup] Table '{$tableName}' - Exists: " . ($exists ? 'YES' : 'NO') . "\n";
            
            Logger::info("SchemaSetup: Table {$tableName} exists check: " . ($exists ? 'YES' : 'NO'));
            
            if ($exists) {
                Logger::debug("SchemaSetup: Table {$tableName} exists, checking for updates");
                // TODO: Implement table update logic
                // For now, skip existing tables
            } else {
                Logger::info("SchemaSetup: Creating table {$tableName}");
                $this->createTable($tableNode);
                $created++;
            }
        }
        
        return ['created' => $created, 'updated' => $updated];
    }
    
    /**
     * Check if table exists
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->connection->query(
                "SELECT to_regclass('public.{$tableName}') AS table_exists"
            );
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result && $result['table_exists'] !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Create table from XML definition
     */
    private function createTable(\SimpleXMLElement $tableNode): void
    {
        $tableName = (string)$tableNode['name'];
        $comment = (string)($tableNode['comment'] ?? '');
        
        $sql = "CREATE TABLE {$tableName} (\n";
        
        $columns = [];
        $primaryKey = null;
        $uniqueConstraints = [];
        $foreignKeys = [];
        $indexes = [];
        
        // Process columns
        foreach ($tableNode->column as $column) {
            $columnSql = $this->buildColumnDefinition($column);
            $columns[] = "  {$columnSql}";
        }
        
        // Process constraints
        foreach ($tableNode->constraint as $constraint) {
            $type = (string)$constraint['type'];
            
            if ($type === 'primary') {
                $pkColumn = (string)$constraint->column['name'];
                $primaryKey = "  PRIMARY KEY ({$pkColumn})";
            } elseif ($type === 'unique') {
                $refId = (string)$constraint['referenceId'];
                $ukColumn = (string)$constraint->column['name'];
                $uniqueConstraints[] = "  CONSTRAINT {$refId} UNIQUE ({$ukColumn})";
            } elseif ($type === 'foreign') {
                $refId = (string)$constraint['referenceId'];
                $column = (string)$constraint['column'];
                $refTable = (string)$constraint['referenceTable'];
                $refColumn = (string)$constraint['referenceColumn'];
                $onDelete = (string)($constraint['onDelete'] ?? 'NO ACTION');
                
                $foreignKeys[] = "  CONSTRAINT {$refId} FOREIGN KEY ({$column}) " .
                                 "REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete}";
            }
        }
        
        // Combine all definitions
        $allDefinitions = array_merge(
            $columns, 
            array_filter([$primaryKey]), 
            $uniqueConstraints,
            $foreignKeys
        );
        $sql .= implode(",\n", $allDefinitions);
        $sql .= "\n)";
        
        if ($comment) {
            // PostgreSQL doesn't support table comments in CREATE TABLE
            // Will add after creation
        }
        
        Logger::debug("SchemaSetup: Executing SQL", ['sql' => $sql]);
        
        $this->connection->exec($sql);
        
        // Create indexes
        foreach ($tableNode->index as $index) {
            $this->createIndex($tableName, $index);
        }
        
        Logger::info("SchemaSetup: Table {$tableName} created successfully");
    }
    
    /**
     * Build column definition SQL
     */
    private function buildColumnDefinition(\SimpleXMLElement $column): string
    {
        $name = (string)$column['name'];
        $type = (string)$column['type'];
        $nullable = ((string)($column['nullable'] ?? 'true')) === 'true';
        $default = (string)($column['default'] ?? '');
        $identity = ((string)($column['identity'] ?? 'false')) === 'true';
        $length = (string)($column['length'] ?? '');
        $comment = (string)($column['comment'] ?? '');
        
        // Map XML types to PostgreSQL types
        $pgType = match($type) {
            'int' => 'INTEGER',
            'varchar' => $length ? "VARCHAR({$length})" : 'VARCHAR(255)',
            'text' => 'TEXT',
            'boolean' => 'BOOLEAN',
            'timestamp' => 'TIMESTAMP',
            default => 'TEXT'
        };
        
        $sql = "{$name} ";
        
        if ($identity) {
            $sql .= "SERIAL PRIMARY KEY";
            return $sql;
        }
        
        $sql .= $pgType;
        
        if (!$nullable) {
            $sql .= " NOT NULL";
        }
        
        if ($default) {
            if ($default === 'CURRENT_TIMESTAMP') {
                $sql .= " DEFAULT CURRENT_TIMESTAMP";
            } elseif ($default === 'true' || $default === 'false') {
                $sql .= " DEFAULT {$default}";
            } else {
                $sql .= " DEFAULT '{$default}'";
            }
        }
        
        return $sql;
    }
    
    /**
     * Create index
     */
    private function createIndex(string $tableName, \SimpleXMLElement $index): void
    {
        $refId = (string)$index['referenceId'];
        $indexType = (string)($index['indexType'] ?? 'btree');
        $columnName = (string)$index->column['name'];
        
        $sql = "CREATE INDEX {$refId} ON {$tableName} USING {$indexType} ({$columnName})";
        
        Logger::debug("SchemaSetup: Creating index", ['sql' => $sql]);
        
        $this->connection->exec($sql);
    }
}
