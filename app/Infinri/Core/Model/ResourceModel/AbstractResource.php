<?php
declare(strict_types=1);

namespace Infinri\Core\Model\ResourceModel;

/**
 * Abstract Resource Model
 * 
 * Base class for all resource models (database table interaction)
 */
abstract class AbstractResource
{
    /**
     * @var string Main table name
     */
    protected string $mainTable;

    /**
     * @var string Primary key field
     */
    protected string $idFieldName = 'id';

    public function __construct(
        protected readonly Connection $connection
    ) {
    }

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
     * Load entity by ID
     *
     * @param int|string $id
     * @return array<string, mixed>|false
     */
    public function load(int|string $id): array|false
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = ? LIMIT 1',
            $this->mainTable,
            $this->idFieldName
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
            
            return (int) $id;
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

        return (int) $this->connection->fetchOne($sql, $params);
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
}
