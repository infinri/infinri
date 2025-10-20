<?php

declare(strict_types=1);

use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Core\Model\ResourceModel\AbstractResource;

// Test resource implementation
class TestResource extends AbstractResource
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->mainTable = 'test_table';
        $this->primaryKey = 'id';  // Set primary key for load() method
    }
}

describe('AbstractResource', function () {
    
    beforeEach(function () {
        // Skip if PostgreSQL PDO driver not available
        if (!extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('PDO PostgreSQL extension not available');
        }
        
        $this->connection = new Connection([
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 5432),
            'database' => getenv('DB_NAME') ?: 'infinri_test',
            'username' => getenv('DB_USER') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: 'postgres',
        ]);
        
        try {
            // Create test table
            $this->connection->query('DROP TABLE IF EXISTS test_table');
            $this->connection->query('
                CREATE TABLE test_table (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    status VARCHAR(50) DEFAULT \'active\',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ');
            
            $this->resource = new TestResource($this->connection);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: ' . $e->getMessage());
        }
    });
    
    afterEach(function () {
        if (isset($this->connection)) {
            try {
                $this->connection->query('DROP TABLE IF EXISTS test_table');
            } catch (\PDOException $e) {
                // Ignore
            }
        }
    });
    
    it('can load entity by ID', function () {
        $id = $this->connection->insert('test_table', ['name' => 'Test Entity']);
        
        $data = $this->resource->load($id);
        
        expect($data)->toBeArray();
        expect($data['id'])->toBe($id);
        expect($data['name'])->toBe('Test Entity');
    });
    
    it('can save new entity', function () {
        $id = $this->resource->save(['name' => 'New Entity']);
        
        expect($id)->toBeGreaterThan(0);
        
        $data = $this->resource->load($id);
        expect($data['name'])->toBe('New Entity');
    });
    
    it('can update existing entity', function () {
        $id = $this->connection->insert('test_table', ['name' => 'Original']);
        
        $updatedId = $this->resource->save(['id' => $id, 'name' => 'Updated']);
        
        expect($updatedId)->toBe($id);
        
        $data = $this->resource->load($id);
        expect($data['name'])->toBe('Updated');
    });
    
    it('can delete entity', function () {
        $id = $this->connection->insert('test_table', ['name' => 'To Delete']);
        
        $affected = $this->resource->delete($id);
        
        expect($affected)->toBe(1);
        
        $data = $this->resource->load($id);
        expect($data)->toBeFalse();
    });
    
    it('can find entities by criteria', function () {
        $this->connection->insert('test_table', ['name' => 'Active User', 'status' => 'active']);
        $this->connection->insert('test_table', ['name' => 'Inactive User', 'status' => 'inactive']);
        $this->connection->insert('test_table', ['name' => 'Another Active', 'status' => 'active']);
        
        $results = $this->resource->findBy(['status' => 'active']);
        
        expect($results)->toHaveCount(2);
        expect($results[0]['status'])->toBe('active');
    });
    
    it('can find one entity by criteria', function () {
        $this->connection->insert('test_table', ['name' => 'John Doe']);
        
        $result = $this->resource->findOneBy(['name' => 'John Doe']);
        
        expect($result)->toBeArray();
        expect($result['name'])->toBe('John Doe');
    });
    
    it('can count entities', function () {
        $this->connection->insert('test_table', ['name' => 'User 1']);
        $this->connection->insert('test_table', ['name' => 'User 2']);
        $this->connection->insert('test_table', ['name' => 'User 3']);
        
        $count = $this->resource->count();
        
        expect($count)->toBe(3);
    });
    
    it('can count with criteria', function () {
        $this->connection->insert('test_table', ['name' => 'Active 1', 'status' => 'active']);
        $this->connection->insert('test_table', ['name' => 'Inactive 1', 'status' => 'inactive']);
        $this->connection->insert('test_table', ['name' => 'Active 2', 'status' => 'active']);
        
        $count = $this->resource->count(['status' => 'active']);
        
        expect($count)->toBe(2);
    });
    
    it('supports limit and offset', function () {
        for ($i = 1; $i <= 10; $i++) {
            $this->connection->insert('test_table', ['name' => "User {$i}"]);
        }
        
        $results = $this->resource->findBy([], 5, 3);
        
        expect($results)->toHaveCount(5);
        expect($results[0]['name'])->toBe('User 4');
    });
    
});
