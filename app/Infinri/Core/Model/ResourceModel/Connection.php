<?php

declare(strict_types=1);

namespace Infinri\Core\Model\ResourceModel;

use PDO;

/**
 * Manages database connections using PDO with connection pooling.
 */
class Connection
{
    /**
     * @var \PDO|null Database connection
     */
    private ?\PDO $connection = null;

    /**
     * @var array<string, mixed> Connection configuration
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => $_ENV['DB_DRIVER'] ?? getenv('DB_DRIVER') ?: 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
            'port' => (int) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 5432),
            'database' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'infinri_test',
            'username' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'infinri',
            'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'infinri',
            'charset' => 'utf8',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                // Enable persistent connections for 20-50ms performance boost
                \PDO::ATTR_PERSISTENT => filter_var(
                    $_ENV['DB_PERSISTENT'] ?? getenv('DB_PERSISTENT') ?: 'false',
                    \FILTER_VALIDATE_BOOLEAN
                ),
            ],
        ], $config);
    }

    /**
     * Get database driver name.
     */
    public function getDriver(): string
    {
        return $this->config['driver'];
    }

    /**
     * Get database connection.
     *
     * @throws \PDOException
     */
    public function getConnection(): \PDO
    {
        if (null === $this->connection) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * Create new database connection.
     *
     * @throws \PDOException
     */
    private function createConnection(): \PDO
    {
        $dsn = $this->buildDsn();

        $connection = new \PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );

        // Set charset for MySQL
        if ('mysql' === $this->config['driver']) {
            $connection->exec("SET NAMES '{$this->config['charset']}'");
        }

        return $connection;
    }

    /**
     * Build DSN string.
     */
    private function buildDsn(): string
    {
        $driver = $this->config['driver'];

        return match ($driver) {
            'mysql' => \sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            ),
            'pgsql' => \sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            ),
            'sqlite' => \sprintf(
                'sqlite:%s',
                $this->config['database']
            ),
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Prepare a SQL statement.
     */
    public function prepare(string $sql): \PDOStatement|false
    {
        return $this->getConnection()->prepare($sql);
    }

    /**
     * Execute a SQL query.
     */
    public function query(string $sql): \PDOStatement|false
    {
        return $this->getConnection()->query($sql);
    }

    /**
     * Execute a SQL statement.
     *
     * @return int|false Number of affected rows
     */
    public function exec(string $sql): int|false
    {
        return $this->getConnection()->exec($sql);
    }

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getConnection()->lastInsertId($name);
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback a transaction.
     */
    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Check if inside a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    /**
     * Quote a string for use in a query.
     */
    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false
    {
        return $this->getConnection()->quote($string, $type);
    }

    /**
     * Execute a query.
     *
     * @param array<mixed> $params
     *
     * @throws \RuntimeException If query preparation or execution fails
     */
    public function pdoQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->prepare($sql);
        if (false === $stmt) {
            throw new \RuntimeException('Failed to prepare query');
        }
        if (! $stmt->execute($params)) {
            throw new \RuntimeException('Failed to execute query');
        }

        return $stmt;
    }

    /**
     * Fetch all rows.
     *
     * @param array<mixed> $params
     *
     * @return array<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->pdoQuery($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row.
     *
     * @param array<mixed> $params
     *
     * @return array<string, mixed>|false
     */
    public function fetchRow(string $sql, array $params = []): array|false
    {
        return $this->pdoQuery($sql, $params)->fetch();
    }

    /**
     * Fetch single value.
     *
     * @param array<mixed> $params
     */
    public function fetchOne(string $sql, array $params = []): mixed
    {
        return $this->pdoQuery($sql, $params)->fetchColumn();
    }

    /**
     * Insert record.
     *
     * @param array<string, mixed> $data
     *
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, \count($columns), '?');

        $sql = \sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->pdoQuery($sql, array_values($data));

        return (int) $this->lastInsertId();
    }

    /**
     * Update records.
     *
     * @param array<string, mixed> $data
     * @param array<mixed>         $whereParams
     *
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }

        $sql = \sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );

        $stmt = $this->pdoQuery($sql, array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    }

    /**
     * Delete records.
     *
     * @param array<mixed> $whereParams
     *
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = \sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->pdoQuery($sql, $whereParams);

        return $stmt->rowCount();
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Get PDO connection.
     */
    public function getPdo(): \PDO
    {
        return $this->getConnection();
    }
}
