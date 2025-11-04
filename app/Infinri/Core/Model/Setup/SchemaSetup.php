<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Setup;

use Infinri\Core\Helper\Logger;
use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Processes db_schema.xml files and creates/updates database tables.
 */
class SchemaSetup
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Get PDO connection.
     */
    public function getPdo(): \PDO
    {
        return $this->connection->getConnection();
    }

    /**
     * Process module's db_schema.xml file.
     *
     * @return array ['created' => int, 'updated' => int]
     *
     * @throws \Exception
     */
    public function processModuleSchema(string $moduleName, string $schemaFile): array
    {
        // Debug: Echo directly so we can see it
        echo "\n  [SchemaSetup] Processing {$moduleName}\n";
        echo "  [SchemaSetup] File: {$schemaFile}\n";

        Logger::info("SchemaSetup: Processing schema for {$moduleName}", [
            'file' => $schemaFile,
        ]);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($schemaFile);

        if (false === $xml) {
            $errors = libxml_get_errors();
            $errorMsg = "SchemaSetup: Failed to parse {$schemaFile}";
            foreach ($errors as $error) {
                $errorMsg .= "\n  - " . trim($error->message);
            }
            libxml_clear_errors();

            Logger::warning($errorMsg);
            file_put_contents(
                __DIR__ . '/../../../../var/schema_debug.log',
                $errorMsg . "\n",
                \FILE_APPEND
            );

            return ['created' => 0, 'updated' => 0];
        }

        $created = 0;
        $updated = 0;

        // Process each table definition
        echo '  [SchemaSetup] Found ' . \count($xml->table) . " tables in XML\n";

        foreach ($xml->table as $tableNode) {
            $tableName = (string) $tableNode['name'];

            $exists = $this->tableExists($tableName);
            echo "  [SchemaSetup] Table '{$tableName}' - Exists: " . ($exists ? 'YES' : 'NO') . "\n";

            Logger::info("SchemaSetup: Table {$tableName} exists check: " . ($exists ? 'YES' : 'NO'));

            if ($exists) {
                Logger::debug("SchemaSetup: Table {$tableName} exists, checking for updates");
                if ($this->updateTable($tableNode)) {
                    $updated++;
                }
            } else {
                Logger::info("SchemaSetup: Creating table {$tableName}");
                $this->createTable($tableNode);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Check if table exists.
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->connection->query(
                "SELECT to_regclass('public.{$tableName}') AS table_exists"
            );
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $exists = $result && null !== $result['table_exists'];

            // Debug logging
            if (! $exists) {
                Logger::debug("SchemaSetup: Table {$tableName} check", [
                    'result' => $result,
                    'exists' => $exists,
                ]);
            }

            return $exists;
        } catch (\Exception $e) {
            Logger::warning("SchemaSetup: Error checking if table {$tableName} exists: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Create table from XML definition.
     */
    private function createTable(\SimpleXMLElement $tableNode): void
    {
        $tableName = (string) $tableNode['name'];
        $comment = (string) ($tableNode['comment'] ?? '');

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
            $type = (string) $constraint['type'];

            if ('primary' === $type) {
                $pkColumn = (string) $constraint->column['name'];
                $primaryKey = "  PRIMARY KEY ({$pkColumn})";
            } elseif ('unique' === $type) {
                $refId = (string) $constraint['referenceId'];
                $ukColumn = (string) $constraint->column['name'];
                $uniqueConstraints[] = "  CONSTRAINT {$refId} UNIQUE ({$ukColumn})";
            } elseif ('foreign' === $type) {
                $refId = (string) $constraint['referenceId'];
                $column = (string) $constraint['column'];
                $refTable = (string) $constraint['referenceTable'];
                $refColumn = (string) $constraint['referenceColumn'];
                $onDelete = (string) ($constraint['onDelete'] ?? 'NO ACTION');

                $foreignKeys[] = "  CONSTRAINT {$refId} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete}";
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

        Logger::debug('SchemaSetup: Executing SQL', ['sql' => $sql]);

        try {
            $result = $this->connection->exec($sql);
            Logger::info("SchemaSetup: Table {$tableName} created successfully (affected rows: " . (false === $result ? '0' : $result) . ')');
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to create table {$tableName}: " . $e->getMessage());
            echo "  ❌ Error creating table {$tableName}: " . $e->getMessage() . "\n";

            throw $e;
        }

        // Create indexes
        foreach ($tableNode->index as $index) {
            $this->createIndex($tableName, $index);
        }

        Logger::info("SchemaSetup: Table {$tableName} created successfully");
    }

    /**
     * Build column definition SQL.
     */
    private function buildColumnDefinition(\SimpleXMLElement $column): string
    {
        $name = (string) $column['name'];
        $type = (string) $column['type'];
        $nullable = ((string) ($column['nullable'] ?? 'true')) === 'true';
        $default = (string) ($column['default'] ?? '');
        $identity = ((string) ($column['identity'] ?? 'false')) === 'true';
        $length = (string) ($column['length'] ?? '');
        $comment = (string) ($column['comment'] ?? '');

        // Map XML types to PostgreSQL types
        $pgType = match ($type) {
            'int' => 'INTEGER',
            'varchar' => $length ? "VARCHAR({$length})" : 'VARCHAR(255)',
            'text' => 'TEXT',
            'boolean' => 'BOOLEAN',
            'timestamp' => 'TIMESTAMP',
            default => 'TEXT'
        };

        $sql = "{$name} ";

        if ($identity) {
            $sql .= 'SERIAL PRIMARY KEY';

            return $sql;
        }

        $sql .= $pgType;

        if (! $nullable) {
            $sql .= ' NOT NULL';
        }

        if ($default) {
            if ('CURRENT_TIMESTAMP' === $default) {
                $sql .= ' DEFAULT CURRENT_TIMESTAMP';
            } elseif ('true' === $default || 'false' === $default) {
                $sql .= " DEFAULT {$default}";
            } else {
                $sql .= " DEFAULT '{$default}'";
            }
        }

        return $sql;
    }

    /**
     * Update existing table to match XML definition.
     *
     * @param \SimpleXMLElement $tableNode Table XML definition
     *
     * @return bool True if table was updated
     *
     * @throws \Exception
     */
    private function updateTable(\SimpleXMLElement $tableNode): bool
    {
        $tableName = (string) $tableNode['name'];
        $updated = false;

        Logger::info("SchemaSetup: Analyzing table {$tableName} for updates");

        try {
            // Get current table structure
            $currentColumns = $this->getTableColumns($tableName);
            $currentIndexes = $this->getTableIndexes($tableName);
            $currentConstraints = $this->getTableConstraints($tableName);

            // Process column changes
            $updated |= $this->processColumnChanges($tableName, $tableNode, $currentColumns);

            // Process index changes
            $updated |= $this->processIndexChanges($tableName, $tableNode, $currentIndexes);

            // Process constraint changes
            $updated |= $this->processConstraintChanges($tableName, $tableNode, $currentConstraints);

            if ($updated) {
                Logger::info("SchemaSetup: Table {$tableName} updated successfully");
                echo "  ✅ Table '{$tableName}' updated\n";
            } else {
                Logger::debug("SchemaSetup: Table {$tableName} is up to date");
                echo "  ✓ Table '{$tableName}' is up to date\n";
            }
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to update table {$tableName}: " . $e->getMessage());
            echo "  ❌ Error updating table {$tableName}: " . $e->getMessage() . "\n";

            throw $e;
        }

        return $updated > 0;
    }

    /**
     * Get current table columns.
     *
     * @param string $tableName Table name
     *
     * @return array Column information
     */
    private function getTableColumns(string $tableName): array
    {
        $sql = "
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length,
                numeric_precision,
                numeric_scale
            FROM information_schema.columns 
            WHERE table_name = :table_name 
            AND table_schema = 'public'
            ORDER BY ordinal_position
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['table_name' => $tableName]);

        $columns = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $columns[$row['column_name']] = $row;
        }

        return $columns;
    }

    /**
     * Get current table indexes.
     *
     * @param string $tableName Table name
     *
     * @return array Index information
     */
    private function getTableIndexes(string $tableName): array
    {
        $sql = "
            SELECT 
                i.indexname,
                i.indexdef,
                array_agg(a.attname ORDER BY a.attnum) as columns
            FROM pg_indexes i
            JOIN pg_class c ON c.relname = i.tablename
            JOIN pg_index idx ON idx.indexrelid = (i.schemaname||'.'||i.indexname)::regclass
            JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum = ANY(idx.indkey)
            WHERE i.tablename = :table_name 
            AND i.schemaname = 'public'
            AND NOT idx.indisprimary
            GROUP BY i.indexname, i.indexdef
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['table_name' => $tableName]);

        $indexes = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $indexes[$row['indexname']] = $row;
        }

        return $indexes;
    }

    /**
     * Get current table constraints.
     *
     * @param string $tableName Table name
     *
     * @return array Constraint information
     */
    private function getTableConstraints(string $tableName): array
    {
        $sql = "
            SELECT 
                tc.constraint_name,
                tc.constraint_type,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.delete_rule
            FROM information_schema.table_constraints tc
            LEFT JOIN information_schema.key_column_usage kcu 
                ON tc.constraint_name = kcu.constraint_name
            LEFT JOIN information_schema.constraint_column_usage ccu 
                ON tc.constraint_name = ccu.constraint_name
            LEFT JOIN information_schema.referential_constraints rc 
                ON tc.constraint_name = rc.constraint_name
            WHERE tc.table_name = :table_name 
            AND tc.table_schema = 'public'
            AND tc.constraint_type != 'CHECK'
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['table_name' => $tableName]);

        $constraints = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $constraints[$row['constraint_name']] = $row;
        }

        return $constraints;
    }

    /**
     * Process column changes (add, modify, remove).
     *
     * @param string               $tableName      Table name
     * @param \SimpleXMLElement    $tableNode      Table XML definition
     * @param array<string, mixed> $currentColumns Current column information
     *
     * @return bool True if changes were made
     *
     * @throws \Exception
     */
    private function processColumnChanges(string $tableName, \SimpleXMLElement $tableNode, array $currentColumns): bool
    {
        $updated = false;
        $xmlColumns = [];

        // Collect XML column definitions
        foreach ($tableNode->column as $column) {
            $columnName = (string) $column['name'];
            $xmlColumns[$columnName] = $column;
        }

        // Add new columns
        foreach ($xmlColumns as $columnName => $column) {
            if (! isset($currentColumns[$columnName])) {
                $this->addColumn($tableName, $column);
                $updated = true;
            } else {
                // Check if column needs modification
                if ($this->columnNeedsUpdate($currentColumns[$columnName], $column)) {
                    $this->modifyColumn($tableName, $column, $currentColumns[$columnName]);
                    $updated = true;
                }
            }
        }

        // Note: We don't automatically remove columns for safety
        // This would require explicit migration scripts

        return $updated;
    }

    /**
     * Add new column to table.
     *
     * @param string            $tableName Table name
     * @param \SimpleXMLElement $column    Column XML definition
     *
     * @throws \Exception
     */
    private function addColumn(string $tableName, \SimpleXMLElement $column): void
    {
        $columnName = (string) $column['name'];
        $columnDef = $this->buildColumnDefinition($column);

        $sql = "ALTER TABLE {$tableName} ADD COLUMN {$columnDef}";

        Logger::info("SchemaSetup: Adding column {$columnName} to {$tableName}");
        Logger::debug('SchemaSetup: Add column SQL', ['sql' => $sql]);

        try {
            $this->connection->exec($sql);
            echo "    + Added column '{$columnName}'\n";
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to add column {$columnName}: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Modify existing column.
     *
     * @param string               $tableName     Table name
     * @param \SimpleXMLElement    $column        Column XML definition
     * @param array<string, mixed> $currentColumn Current column information
     *
     * @throws \Exception
     */
    private function modifyColumn(string $tableName, \SimpleXMLElement $column, array $currentColumn): void
    {
        $columnName = (string) $column['name'];
        $type = (string) $column['type'];
        $nullable = ((string) ($column['nullable'] ?? 'true')) === 'true';
        $default = (string) ($column['default'] ?? '');
        $length = (string) ($column['length'] ?? '');

        Logger::info("SchemaSetup: Modifying column {$columnName} in {$tableName}");

        try {
            // Change data type if needed
            $newType = $this->mapXmlTypeToPostgres($type, $length);
            $currentType = $this->normalizePostgresType($currentColumn['data_type'], $currentColumn['character_maximum_length']);

            if ($newType !== $currentType) {
                $sql = "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} TYPE {$newType}";
                $this->connection->exec($sql);
                echo "    ~ Modified column '{$columnName}' type to {$newType}\n";
            }

            // Change nullable constraint if needed
            $currentNullable = 'YES' === $currentColumn['is_nullable'];
            if ($nullable !== $currentNullable) {
                $constraint = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
                $sql = "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} {$constraint}";
                $this->connection->exec($sql);
                echo "    ~ Modified column '{$columnName}' nullable to " . ($nullable ? 'YES' : 'NO') . "\n";
            }

            // Change default value if needed
            if ($default && $default !== ($currentColumn['column_default'] ?? '')) {
                $defaultValue = $this->formatDefaultValue($default);
                $sql = "ALTER TABLE {$tableName} ALTER COLUMN {$columnName} SET DEFAULT {$defaultValue}";
                $this->connection->exec($sql);
                echo "    ~ Modified column '{$columnName}' default value\n";
            }
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to modify column {$columnName}: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Check if column needs update.
     *
     * @param array             $currentColumn Current column information
     * @param \SimpleXMLElement $xmlColumn     XML column definition
     *
     * @return bool True if column needs update
     */
    /**
     * @param array<string, mixed> $currentColumn
     */
    private function columnNeedsUpdate(array $currentColumn, \SimpleXMLElement $xmlColumn): bool
    {
        $type = (string) $xmlColumn['type'];
        $nullable = ((string) ($xmlColumn['nullable'] ?? 'true')) === 'true';
        $default = (string) ($xmlColumn['default'] ?? '');
        $length = (string) ($xmlColumn['length'] ?? '');

        // Check type
        $newType = $this->mapXmlTypeToPostgres($type, $length);
        $currentType = $this->normalizePostgresType($currentColumn['data_type'], $currentColumn['character_maximum_length']);
        if ($newType !== $currentType) {
            return true;
        }

        // Check nullable
        $currentNullable = 'YES' === $currentColumn['is_nullable'];
        if ($nullable !== $currentNullable) {
            return true;
        }

        // Check default (simplified check)
        if ($default && $default !== ($currentColumn['column_default'] ?? '')) {
            return true;
        }

        return false;
    }

    /**
     * Map XML type to PostgreSQL type.
     *
     * @param string $type   XML type
     * @param string $length Length specification
     *
     * @return string PostgreSQL type
     */
    private function mapXmlTypeToPostgres(string $type, string $length = ''): string
    {
        return match ($type) {
            'int' => 'integer',
            'varchar' => $length ? "character varying({$length})" : 'character varying(255)',
            'text' => 'text',
            'boolean' => 'boolean',
            'timestamp' => 'timestamp without time zone',
            default => 'text'
        };
    }

    /**
     * Normalize PostgreSQL type for comparison.
     *
     * @param string   $type   PostgreSQL type
     * @param int|null $length Character maximum length
     *
     * @return string Normalized type
     */
    private function normalizePostgresType(string $type, ?int $length): string
    {
        return match ($type) {
            'character varying' => $length ? "character varying({$length})" : 'character varying(255)',
            'timestamp without time zone' => 'timestamp without time zone',
            default => $type
        };
    }

    /**
     * Format default value for SQL.
     *
     * @param string $default Default value
     *
     * @return string Formatted default value
     */
    private function formatDefaultValue(string $default): string
    {
        if ('CURRENT_TIMESTAMP' === $default) {
            return 'CURRENT_TIMESTAMP';
        } elseif ('true' === $default || 'false' === $default) {
            return $default;
        } else {
            return "'{$default}'";
        }
    }

    /**
     * Process index changes.
     *
     * @param string               $tableName      Table name
     * @param \SimpleXMLElement    $tableNode      Table XML definition
     * @param array<string, mixed> $currentIndexes Current index information
     *
     * @return bool True if changes were made
     */
    private function processIndexChanges(string $tableName, \SimpleXMLElement $tableNode, array $currentIndexes): bool
    {
        $updated = false;
        $xmlIndexes = [];

        // Collect XML index definitions
        foreach ($tableNode->index as $index) {
            $refId = (string) $index['referenceId'];
            $xmlIndexes[$refId] = $index;
        }

        // Add new indexes
        foreach ($xmlIndexes as $refId => $index) {
            if (! isset($currentIndexes[$refId])) {
                $this->createIndex($tableName, $index);
                $updated = true;
            }
        }

        // Remove obsolete indexes (optional - could be dangerous)
        // For now, we only add new indexes, don't remove existing ones

        return $updated;
    }

    /**
     * Process constraint changes.
     *
     * @param string               $tableName          Table name
     * @param \SimpleXMLElement    $tableNode          Table XML definition
     * @param array<string, mixed> $currentConstraints Current constraint information
     *
     * @return bool True if changes were made
     */
    private function processConstraintChanges(string $tableName, \SimpleXMLElement $tableNode, array $currentConstraints): bool
    {
        $updated = false;
        $xmlConstraints = [];

        // Collect XML constraint definitions
        foreach ($tableNode->constraint as $constraint) {
            $refId = (string) $constraint['referenceId'];
            $xmlConstraints[$refId] = $constraint;
        }

        // Add new constraints
        foreach ($xmlConstraints as $refId => $constraint) {
            if (! isset($currentConstraints[$refId])) {
                $this->addConstraint($tableName, $constraint);
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Add constraint to table.
     *
     * @param string            $tableName  Table name
     * @param \SimpleXMLElement $constraint Constraint XML definition
     */
    private function addConstraint(string $tableName, \SimpleXMLElement $constraint): void
    {
        $refId = (string) $constraint['referenceId'];
        $type = (string) $constraint['type'];

        if ('unique' === $type) {
            $column = (string) $constraint->column['name'];
            $sql = "ALTER TABLE {$tableName} ADD CONSTRAINT {$refId} UNIQUE ({$column})";
        } elseif ('foreign' === $type) {
            $column = (string) $constraint['column'];
            $refTable = (string) $constraint['referenceTable'];
            $refColumn = (string) $constraint['referenceColumn'];
            $onDelete = (string) ($constraint['onDelete'] ?? 'NO ACTION');

            $sql = "ALTER TABLE {$tableName} ADD CONSTRAINT {$refId} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete}";
        } else {
            return; // Skip unsupported constraint types
        }

        Logger::info("SchemaSetup: Adding constraint {$refId} to {$tableName}");
        Logger::debug('SchemaSetup: Add constraint SQL', ['sql' => $sql]);

        try {
            $this->connection->exec($sql);
            echo "    + Added constraint '{$refId}'\n";
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to add constraint {$refId}: " . $e->getMessage());
            echo "    ⚠ Warning adding constraint {$refId}: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Create index.
     */
    private function createIndex(string $tableName, \SimpleXMLElement $index): void
    {
        $refId = (string) $index['referenceId'];
        $indexType = (string) ($index['indexType'] ?? 'btree');
        $columnName = (string) $index->column['name'];

        $sql = "CREATE INDEX {$refId} ON {$tableName} USING {$indexType} ({$columnName})";

        Logger::debug('SchemaSetup: Creating index', ['sql' => $sql]);

        try {
            $this->connection->exec($sql);
            Logger::info("SchemaSetup: Index {$refId} created successfully");
            echo "    + Added index '{$refId}'\n";
        } catch (\Exception $e) {
            Logger::error("SchemaSetup: Failed to create index {$refId}: " . $e->getMessage());
            echo "  ⚠ Warning creating index {$refId}: " . $e->getMessage() . "\n";
        }
    }
}
