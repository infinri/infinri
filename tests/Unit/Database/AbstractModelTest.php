<?php

declare(strict_types=1);

use Infinri\Core\Model\AbstractModel;
use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Core\Model\ResourceModel\AbstractResource;

// Test resource
class ModelTestResource extends AbstractResource
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->mainTable = 'model_test';
        $this->primaryKey = 'id';  // Set primary key for load() method
    }
}

// Test model
class TestModel extends AbstractModel
{
    public function __construct(
        private readonly ModelTestResource $resource
    ) {
    }
    
    protected function getResource(): AbstractResource
    {
        return $this->resource;
    }
}

describe('AbstractModel', function () {
    
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
            $this->connection->query('DROP TABLE IF EXISTS model_test');
            $this->connection->query('
                CREATE TABLE model_test (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255),
                    status VARCHAR(50) DEFAULT \'active\'
                )
            ');
            
            $this->resource = new ModelTestResource($this->connection);
            $this->model = new TestModel($this->resource);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Cannot connect to PostgreSQL: ' . $e->getMessage());
        }
    });
    
    afterEach(function () {
        if (isset($this->connection)) {
            try {
                $this->connection->query('DROP TABLE IF EXISTS model_test');
            } catch (\PDOException $e) {
                // Ignore
            }
        }
    });
    
    it('can set and get data', function () {
        $this->model->setData('name', 'John Doe');
        
        expect($this->model->getData('name'))->toBe('John Doe');
    });
    
    it('can set data as array', function () {
        $this->model->setData(['name' => 'Jane', 'email' => 'jane@test.com']);
        
        expect($this->model->getData('name'))->toBe('Jane');
        expect($this->model->getData('email'))->toBe('jane@test.com');
    });
    
    it('can save new model', function () {
        $this->model->setData(['name' => 'New User', 'email' => 'new@test.com']);
        $this->model->save();
        
        expect($this->model->getId())->not->toBeNull();
        expect($this->model->isObjectNew())->toBeFalse();
    });
    
    it('can load model by ID', function () {
        $id = $this->connection->insert('model_test', ['name' => 'Loaded User', 'email' => 'load@test.com']);
        
        $model = new TestModel($this->resource);
        $model->load($id);
        
        expect($model->getId())->toBe($id);
        expect($model->getData('name'))->toBe('Loaded User');
    });
    
    it('can update existing model', function () {
        $this->model->setData(['name' => 'Original', 'email' => 'original@test.com']);
        $this->model->save();
        
        $id = $this->model->getId();
        
        $this->model->setData('name', 'Updated');
        $this->model->save();
        
        expect($this->model->getId())->toBe($id);
        expect($this->model->getData('name'))->toBe('Updated');
    });
    
    it('can delete model', function () {
        $this->model->setData(['name' => 'To Delete']);
        $this->model->save();
        
        $id = $this->model->getId();
        $this->model->delete();
        
        expect($this->model->isDeleted())->toBeTrue();
        
        $data = $this->resource->load($id);
        expect($data)->toBeFalse();
    });
    
    it('detects data changes', function () {
        $this->model->setData(['name' => 'Original']);
        $this->model->save();
        
        expect($this->model->hasDataChanged())->toBeFalse();
        
        $this->model->setData('name', 'Changed');
        
        expect($this->model->hasDataChanged())->toBeTrue();
        expect($this->model->hasDataChanged('name'))->toBeTrue();
    });
    
    it('identifies new objects', function () {
        expect($this->model->isObjectNew())->toBeTrue();
        
        $this->model->setData(['name' => 'Test']);
        $this->model->save();
        
        expect($this->model->isObjectNew())->toBeFalse();
    });
    
    it('supports magic getters and setters', function () {
        $this->model->name = 'Magic Name';
        
        expect($this->model->name)->toBe('Magic Name');
        expect(isset($this->model->name))->toBeTrue();
    });
    
    it('can convert to array', function () {
        $this->model->setData(['name' => 'Test', 'email' => 'test@test.com']);
        
        $array = $this->model->toArray();
        
        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array)->toHaveKey('email');
    });
    
    it('prevents saving deleted model', function () {
        $this->model->setData(['name' => 'Test']);
        $this->model->save();
        $this->model->delete();
        
        $this->model->setData('name', 'Changed');
        $this->model->save(); // Should throw exception
    })->throws(RuntimeException::class, 'Cannot save deleted model');
    
});
