<?php
declare(strict_types=1);

namespace Infinri\Core\Model\ResourceModel;

/**
 * Base class for all resource models (database table interaction)
 */
abstract class AbstractResource
{
    /**
     * @var string Main database table name
     */
    protected string $mainTable;

    /**
     * @var string Primary key field name
     */
    protected string $primaryKey;

    /**
     * @var string Primary key field
     */
    protected string $idFieldName = 'id';

    /**
     * @var array<string>|null Cached table columns
     */
    private ?array $tableColumns = null;

    public function __construct(
        protected readonly Connection $connection
    ) {}

    /**
     * Get main table name
     *
     * @return string
     */
    public function getMainTable(): string
    {
        return $this->mainTable;
    }

    /**
     * Get primary key field name
     *
     * @return string
     */
    public function getIdFieldName(): string
    {
        return $this->idFieldName;
    }

    /**
     * Load entity data by ID
     *
     * @param int|string $id
     * @return array|false
     */
    public function load(int|string $id): array|false
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 1',
            $this->mainTable,
            $this->primaryKey
        );

        return $this->connection->fetchRow($sql, [$id]);
    }

    /**
     * Save entity
     *
     * @param array<string, mixed> $data
     * @return int Entity ID
     */
    public function save(array $data): int
    {
        if (isset($data[$this->idFieldName]) && $data[$this->idFieldName]) {
            // Update existing
            $id = $data[$this->idFieldName];
            unset($data[$this->idFieldName]);

            $this->connection->update(
                $this->mainTable,
                $data,
                "{$this->idFieldName} = ?",
                [$id]
            );

            return (int)$id;
        } else {
            // Insert new
            unset($data[$this->idFieldName]);
            return $this->connection->insert($this->mainTable, $data);
        }
    }

    /**
     * Delete entity by ID
     *
     * @param int|string $id
     * @return int Number of deleted rows
     */
    public function delete(int|string $id): int
    {
        return $this->connection->delete(
            $this->mainTable,
            "{$this->idFieldName} = ?",
            [$id]
        );
    }

    /**
     * Find entities by criteria
     *
     * @param array<string, mixed> $criteria
     * @param int|null $limit
     * @param int|null $offset
     * @return array<array<string, mixed>>
     */
    public function findBy(array $criteria, ?int $limit = null, ?int $offset = null): array
    {
        $where = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            // Validate column name to prevent SQL injection
            $this->validateColumnName($field);

            if ($value === null) {
                $where[] = "{$field} IS NULL";
            } else {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        $sql = sprintf('SELECT * FROM %s', $this->mainTable);

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset !== null) {
                $sql .= " OFFSET {$offset}";
            }
        }

        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Find one entity by criteria
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|false
     */
    public function findOneBy(array $criteria): array|false
    {
        $results = $this->findBy($criteria, 1);
        return $results[0] ?? false;
    }

    /**
     * Count entities by criteria
     *
     * @param array<string, mixed> $criteria
     * @return int
     */
    public function count(array $criteria = []): int
    {
        $where = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            // Validate column name to prevent SQL injection
            $this->validateColumnName($field);

            if ($value === null) {
                $where[] = "{$field} IS NULL";
            } else {
                $where[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        $sql = sprintf('SELECT COUNT(*) FROM %s', $this->mainTable);

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int)$this->connection->fetchOne($sql, $params);
    }

    /**
     * Get connection
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get primary key
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get table columns from database schema
     *
     * @return array<string>
     */
    protected function getTableColumns(): array
    {
        if ($this->tableColumns === null) {
            $driver = $this->connection->getDriver();

            // Use database-specific SQL to get column names
            if ($driver === 'mysql') {
                $sql = "SHOW COLUMNS FROM {$this->mainTable}";
                $columns = $this->connection->fetchAll($sql);
                $this->tableColumns = array_column($columns, 'Field');
            } elseif ($driver === 'pgsql') {
                $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = ?";
                $columns = $this->connection->fetchAll($sql, [$this->mainTable]);
                $this->tableColumns = array_column($columns, 'column_name');
            } else {
                // Fallback: Use PDO's metadata (works for most databases but slower)
                $sql = "SELECT * FROM {$this->mainTable} LIMIT 0";
                $stmt = $this->connection->getConnection()->query($sql);
                $this->tableColumns = [];
                for ($i = 0; $i < $stmt->columnCount(); $i++) {
                    $col = $stmt->getColumnMeta($i);
                    $this->tableColumns[] = $col['name'];
                }
            }
        }

        return $this->tableColumns;
    }

    /**
     * Validate that field name is a valid column in the table
     *
     * @param string $field
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateColumnName(string $field): void
    {
        $validColumns = $this->getTableColumns();

        if (!in_array($field, $validColumns, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid column name "%s" for table "%s". Valid columns: %s',
                    $field,
                    $this->mainTable,
                    implode(', ', $validColumns)
                )
            );
        }
    }
}
