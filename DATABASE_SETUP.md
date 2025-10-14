# Database Setup Guide

## Phase 7: Database Layer

The Infinri framework now includes a complete database layer with:
- PDO-based connection management
- Active Record pattern
- Repository pattern
- Support for MySQL, PostgreSQL, and SQLite

## PostgreSQL Setup for Testing

### 1. Install PostgreSQL

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib

# Install PHP PostgreSQL extension
sudo apt-get install php-pgsql php8.1-pgsql

# Verify installation
php -m | grep pgsql
```

### 2. Create Test Database

```bash
# Switch to postgres user
sudo -u postgres psql

# In PostgreSQL console:
CREATE DATABASE infinri_test;
CREATE USER infinri WITH PASSWORD 'infinri';
GRANT ALL PRIVILEGES ON DATABASE infinri_test TO infinri;

# Exit
\q
```

### 3. Configure Environment Variables

Create a `.env` file in the project root or export these:

```bash
export DB_HOST=localhost
export DB_PORT=5432
export DB_NAME=infinri_test
export DB_USER=infinri
export DB_PASSWORD=infinri
```

### 4. Run Tests

```bash
# Run all tests including database tests
composer test

# Run only database tests
vendor/bin/pest tests/Unit/Database/
```

## Production Database Configuration

### Option 1: Configuration File

Create `app/etc/database.php`:

```php
<?php
return [
    'driver' => 'pgsql', // or 'mysql', 'sqlite'
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int)(getenv('DB_PORT') ?: 5432),
    'database' => getenv('DB_NAME') ?: 'infinri',
    'username' => getenv('DB_USER') ?: 'infinri',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8',
];
```

### Option 2: DI Configuration

Add to `app/Infinri/Core/etc/di.xml`:

```xml
<type name="Infinri\Core\Model\ResourceModel\Connection">
    <arguments>
        <argument name="config" xsi:type="array">
            <item name="driver" xsi:type="string">pgsql</item>
            <item name="host" xsi:type="string">localhost</item>
            <item name="database" xsi:type="string">infinri</item>
            <item name="username" xsi:type="string">infinri</item>
            <item name="password" xsi:type="string">secret</item>
        </argument>
    </arguments>
</type>
```

## Example Usage

### Simple Model Example

```php
use Infinri\Core\Model\User;
use Infinri\Core\Model\ResourceModel\User as UserResource;
use Infinri\Core\Model\ResourceModel\Connection;

// Get connection from DI
$connection = $objectManager->get(Connection::class);
$userResource = new UserResource($connection);

// Create new user
$user = new User($userResource);
$user->setName('John Doe');
$user->setEmail('john@example.com');
$user->save();

// Load user
$user->load(1);
echo $user->getName(); // John Doe

// Update user
$user->setName('Jane Doe');
$user->save();

// Delete user
$user->delete();
```

### Repository Pattern Example

```php
use Infinri\Core\Model\Repository\UserRepository;

$userRepository = $objectManager->get(UserRepository::class);

// Get by ID
$user = $userRepository->getById(1);

// Get by email
$user = $userRepository->getByEmail('john@example.com');

// Get list
$users = $userRepository->getList(['status' => 'active']);

// Save
$userRepository->save($user);

// Delete
$userRepository->delete($user);
```

### Direct Database Queries

```php
use Infinri\Core\Model\ResourceModel\Connection;

$connection = $objectManager->get(Connection::class);

// Insert
$id = $connection->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
$connection->update('users', 
    ['name' => 'Jane Doe'],
    'id = ?',
    [1]
);

// Select
$users = $connection->fetchAll('SELECT * FROM users WHERE status = ?', ['active']);

// Transaction
$connection->beginTransaction();
try {
    $connection->insert('users', ['name' => 'User 1']);
    $connection->insert('users', ['name' => 'User 2']);
    $connection->commit();
} catch (\Exception $e) {
    $connection->rollback();
}
```

## Database Migrations (Future)

While migrations aren't implemented yet, you can create tables manually:

### Users Table (PostgreSQL)

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
```

### Users Table (MySQL)

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Troubleshooting

### "could not find driver" Error

**Solution:** Install PHP PDO extension for your database:

```bash
# PostgreSQL
sudo apt-get install php-pgsql

# MySQL
sudo apt-get install php-mysql

# Restart PHP
sudo systemctl restart php8.1-fpm
```

### Connection Refused

**Solution:** Check PostgreSQL is running:

```bash
sudo systemctl status postgresql
sudo systemctl start postgresql
```

### Authentication Failed

**Solution:** Update `pg_hba.conf`:

```bash
sudo nano /etc/postgresql/*/main/pg_hba.conf

# Change this line:
local   all   all   peer

# To:
local   all   all   md5

# Restart
sudo systemctl restart postgresql
```

## Next Steps

1. ✅ PostgreSQL installed and configured
2. ✅ Test database created
3. ✅ All 237 tests passing
4. Ready to build real application features!
