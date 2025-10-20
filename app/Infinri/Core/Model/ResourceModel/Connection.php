<?php
declare(strict_types=1);

namespace Infinri\Core\Model\ResourceModel;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * 
 * Manages database connections using PDO with connection pooling
 */
class Connection
{
    /**
     * @var PDO|null Database connection
     */
    private ?PDO $connection = null;

    /**
     * @var array<string, mixed> Connection configuration
     */
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'driver' => getenv('DB_DRIVER') ?: 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
            'database' => getenv('DB_NAME') ?: 'infinri_test',
            'username' => getenv('DB_USER') ?: 'infinri',
            'password' => getenv('DB_PASSWORD') ?: 'infinri',
            'charset' => 'utf8',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ], $config);
    }

    /**
     * Get database connection
     *
     * @return PDO
     * @throws PDOException
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * Create new database connection
     *
     * @return PDO
     * @throws PDOException
     */
    private function createConnection(): PDO
    {
        $dsn = $this->buildDsn();
        
        $connection = new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );

        // Set charset for MySQL
        if ($this->config['driver'] === 'mysql') {
            $connection->exec("SET NAMES '{$this->config['charset']}'");
        }

        return $connection;
    }

    /**
     * Build DSN string
     *
     * @return string
     */
    private function buildDsn(): string
    {
        $driver = $this->config['driver'];

        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database']
            ),
            'sqlite' => sprintf(
                'sqlite:%s',
                $this->config['database']
            ),
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}")
        };
    }

    /**
     * Prepare a SQL statement
     *
     * @param string $sql
     * @return \PDOStatement|false
     */
    public function prepare(string $sql): \PDOStatement|false
    {
        return $this->getConnection()->prepare($sql);
    }
    
    /**
     * Execute a SQL query
     *
     * @param string $sql
     * @return \PDOStatement|false
     */
    public function query(string $sql): \PDOStatement|false
    {
        return $this->getConnection()->query($sql);
    }
    
    /**
     * Execute a SQL statement
     *
     * @param string $sql
     * @return int|false Number of affected rows
     */
    public function exec(string $sql): int|false
    {
        return $this->getConnection()->exec($sql);
    }
    
    /**
     * Get the last inserted ID
     *
     * @param string|null $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getConnection()->lastInsertId($name);
    }
    
    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Check if inside a transaction
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }
    
    /**
     * Quote a string for use in a query
     *
     * @param string $string
     * @param int $type
     * @return string|false
     */
    public function quote(string $string, int $type = \PDO::PARAM_STR): string|false
    {
        return $this->getConnection()->quote($string, $type);
    }

    /**
     * Execute a query
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return \PDOStatement
     */
    public function pdoQuery(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return array<array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->pdoQuery($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return array<string, mixed>|false
     */
    public function fetchRow(string $sql, array $params = []): array|false
    {
        return $this->pdoQuery($sql, $params)->fetch();
    }

    /**
     * Fetch single value
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return mixed
     */
    public function fetchOne(string $sql, array $params = []): mixed
    {
        return $this->pdoQuery($sql, $params)->fetchColumn();
    }

    /**
     * Insert record
     *
     * @param string $table
     * @param array<string, mixed> $data
     * @return int Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->pdoQuery($sql, array_values($data));

        return (int) $this->lastInsertId();
    }

    /**
     * Update records
     *
     * @param string $table
     * @param array<string, mixed> $data
     * @param string $where
     * @param array<mixed> $whereParams
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = ?";
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set),
            $where
        );

        $stmt = $this->pdoQuery($sql, array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    }

    /**
     * Delete records
     *
     * @param string $table
     * @param string $where
     * @param array<mixed> $whereParams
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $stmt = $this->pdoQuery($sql, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Close connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Get PDO connection
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->getConnection();
    }
}
