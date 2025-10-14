<?php

declare(strict_types=1);

use Infinri\Core\Model\ResourceModel\Connection;

describe('Connection', function () {
    
    beforeEach(function () {
        // Skip if PostgreSQL PDO driver not available
        if (!extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('PDO PostgreSQL extension not available');
        }
        
        // Use PostgreSQL for testing
        // You can override these via environment variables
        $this->connection = new Connection([
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
            'database' => getenv('DB_NAME') ?: 'infinri_test',
            'username' => getenv('DB_USER') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: 'postgres',
        ]);
        
        try {
            // Drop and create test table
            $this->connection->query('DROP TABLE IF EXISTS test_table');
            $this->connection->query('
                CREATE TABLE test_table (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
        } catch (\PDOException $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: ' . $e->getMessage());
        }
    });
    
    afterEach(function () {
        // Cleanup
        if (isset($this->connection)) {
            try {
                $this->connection->query('DROP TABLE IF EXISTS test_table');
            } catch (\PDOException $e) {
                // Ignore cleanup errors
            }
        }
    });
    
    it('can establish database connection', function () {
        $pdo = $this->connection->getConnection();
        
        expect($pdo)->toBeInstanceOf(PDO::class);
    });
    
    it('can insert record', function () {
        $id = $this->connection->insert('test_table', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        expect($id)->toBeGreaterThan(0);
    });
    
    it('can fetch all records', function () {
        $this->connection->insert('test_table', ['name' => 'User 1', 'email' => 'user1@test.com']);
        $this->connection->insert('test_table', ['name' => 'User 2', 'email' => 'user2@test.com']);
        
        $results = $this->connection->fetchAll('SELECT * FROM test_table');
        
        expect($results)->toHaveCount(2);
        expect($results[0]['name'])->toBe('User 1');
    });
    
    it('can fetch single row', function () {
        $id = $this->connection->insert('test_table', ['name' => 'Test User', 'email' => 'test@test.com']);
        
        $row = $this->connection->fetchRow('SELECT * FROM test_table WHERE id = ?', [$id]);
        
        expect($row)->toBeArray();
        expect($row['name'])->toBe('Test User');
    });
    
    it('can fetch single value', function () {
        $this->connection->insert('test_table', ['name' => 'Test', 'email' => 'test@test.com']);
        
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM test_table');
        
        expect($count)->toBe('1');
    });
    
    it('can update record', function () {
        $id = $this->connection->insert('test_table', ['name' => 'Old Name', 'email' => 'old@test.com']);
        
        $affected = $this->connection->update(
            'test_table',
            ['name' => 'New Name'],
            'id = ?',
            [$id]
        );
        
        expect($affected)->toBe(1);
        
        $row = $this->connection->fetchRow('SELECT * FROM test_table WHERE id = ?', [$id]);
        expect($row['name'])->toBe('New Name');
    });
    
    it('can delete record', function () {
        $id = $this->connection->insert('test_table', ['name' => 'To Delete', 'email' => 'delete@test.com']);
        
        $affected = $this->connection->delete('test_table', 'id = ?', [$id]);
        
        expect($affected)->toBe(1);
        
        $row = $this->connection->fetchRow('SELECT * FROM test_table WHERE id = ?', [$id]);
        expect($row)->toBeFalse();
    });
    
    it('supports transactions', function () {
        $this->connection->beginTransaction();
        
        $this->connection->insert('test_table', ['name' => 'User 1', 'email' => 'user1@test.com']);
        
        expect($this->connection->inTransaction())->toBeTrue();
        
        $this->connection->rollback();
        
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM test_table');
        expect($count)->toBe('0');
    });
    
    it('can commit transaction', function () {
        $this->connection->beginTransaction();
        
        $this->connection->insert('test_table', ['name' => 'User 1', 'email' => 'user1@test.com']);
        $this->connection->commit();
        
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM test_table');
        expect($count)->toBe('1');
    });
    
    it('supports prepared statements with parameters', function () {
        $this->connection->insert('test_table', ['name' => 'John', 'email' => 'john@test.com']);
        $this->connection->insert('test_table', ['name' => 'Jane', 'email' => 'jane@test.com']);
        
        $results = $this->connection->fetchAll('SELECT * FROM test_table WHERE name = ?', ['John']);
        
        expect($results)->toHaveCount(1);
        expect($results[0]['name'])->toBe('John');
    });
    
});
