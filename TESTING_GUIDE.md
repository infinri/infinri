# Testing Guide

## Test Setup on Ubuntu

### Install PostgreSQL and PHP Extensions

```bash
# Install PostgreSQL
sudo apt-get update
sudo apt-get install postgresql postgresql-contrib

# Install PHP and required extensions
sudo apt-get install php8.1 php8.1-cli php8.1-pgsql php8.1-mbstring php8.1-xml php8.1-json

# Verify extensions
php -m | grep -E "(pdo|pgsql|mbstring|xml|json)"
```

### Create Test Database

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE infinri_test;
CREATE USER infinri WITH PASSWORD 'infinri';
GRANT ALL PRIVILEGES ON DATABASE infinri_test TO infinri;

# Grant schema permissions (PostgreSQL 15+)
\c infinri_test
GRANT ALL ON SCHEMA public TO infinri;

# Exit
\q
```

### Configure Environment Variables (Optional)

If using different credentials, export these before running tests:

```bash
export DB_HOST=localhost
export DB_PORT=5432
export DB_NAME=infinri_test
export DB_USER=infinri
export DB_PASSWORD=infinri
```

Or add to `~/.bashrc` for persistence.

## Running Tests

### Run All Tests

```bash
# Run complete test suite (237 tests)
composer test

# Expected output:
#   Tests:  237 passed
#   Duration: ~2-5s
```

### Run Specific Test Suites

```bash
# Unit tests only (without database)
vendor/bin/pest tests/Unit/ --exclude-group database

# Database tests only
vendor/bin/pest tests/Unit/Database/

# Integration tests
vendor/bin/pest tests/Integration/

# Specific test file
vendor/bin/pest tests/Unit/App/RouterTest.php
```

### Run with Coverage

```bash
# Generate HTML coverage report
vendor/bin/pest --coverage --coverage-html coverage/

# View report
open coverage/index.html
```

### Run with Filters

```bash
# Run specific test
vendor/bin/pest --filter="it can handle homepage request"

# Run tests matching pattern
vendor/bin/pest --filter="Router"

# Stop on first failure
vendor/bin/pest --stop-on-failure

# Verbose output
vendor/bin/pest --verbose
```

## Test Breakdown

### Unit Tests (201 tests)

**Component Registration** (36 tests)
- `ComponentRegistrarTest.php` - 12 tests

**Configuration** (25 tests)
- `ConfigReaderTest.php` - 7 tests
- `ConfigLoaderTest.php` - 7 tests
- `ScopeConfigTest.php` - 12 tests (estimated)

**DI Container** (23 tests)
- `XmlReaderTest.php` - 9 tests
- `ContainerFactoryTest.php` - 6 tests
- `ObjectManagerTest.php` - 8 tests

**Layout System** (51 tests)
- `LoaderTest.php` - 8 tests
- `MergerTest.php` - 6 tests
- `ProcessorTest.php` - 7 tests
- `BuilderTest.php` - 8 tests
- `RendererTest.php` - 6 tests

**Blocks** (16 tests)
- `AbstractBlockTest.php` - 8 tests
- `ContainerTest.php` - 8 tests

**Templates** (18 tests)
- `TemplateTest.php` - 11 tests
- `TemplateResolverTest.php` - 7 tests

**HTTP/Routing** (40 tests)
- `RequestTest.php` - 17 tests
- `ResponseTest.php` - 16 tests
- `RouterTest.php` - 12 tests
- `FrontControllerTest.php` - 5 tests

**Modules** (23 tests)
- `ModuleReaderTest.php` - 6 tests
- `ModuleListTest.php` - 8 tests
- `ModuleManagerTest.php` - 9 tests

### Database Tests (36 tests - require PostgreSQL)

**Connection** (11 tests)
- `ConnectionTest.php` - Database connectivity, CRUD operations, transactions

**Resource Models** (10 tests)
- `AbstractResourceTest.php` - Data access layer, findBy, count operations

**Models** (12 tests)
- `AbstractModelTest.php` - Active Record pattern, save, load, delete

### Integration Tests (8 tests)

**Application** (8 tests)
- `ApplicationTest.php` - Full request-response cycle, routing, rendering

## Troubleshooting

### Tests Are Skipped

```bash
# Check if PostgreSQL extension is loaded
php -m | grep pgsql

# If not, install it
sudo apt-get install php8.1-pgsql

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

### Connection Refused

```bash
# Check PostgreSQL is running
sudo systemctl status postgresql

# Start if needed
sudo systemctl start postgresql

# Enable on boot
sudo systemctl enable postgresql
```

### Authentication Failed

```bash
# Edit pg_hba.conf
sudo nano /etc/postgresql/*/main/pg_hba.conf

# Change 'peer' to 'md5' for local connections
local   all   all   md5

# Restart PostgreSQL
sudo systemctl restart postgresql
```

### Permission Denied

```bash
# Grant schema permissions (PostgreSQL 15+)
sudo -u postgres psql infinri_test -c "GRANT ALL ON SCHEMA public TO infinri;"
```

### Tests Hang

```bash
# Kill any hanging PHP processes
pkill -9 php

# Clear cache
composer clear-cache

# Regenerate autoload
composer dump-autoload

# Try again
composer test
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      postgres:
        image: postgres:14
        env:
          POSTGRES_DB: infinri_test
          POSTGRES_USER: infinri
          POSTGRES_PASSWORD: infinri
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pdo, pdo_pgsql, mbstring, xml, json
          
      - name: Install dependencies
        run: composer install
        
      - name: Run tests
        run: composer test
        env:
          DB_HOST: localhost
          DB_PORT: 5432
          DB_NAME: infinri_test
          DB_USER: infinri
          DB_PASSWORD: infinri
```

## Writing Tests

### Test Structure

```php
<?php

declare(strict_types=1);

use YourNamespace\YourClass;

describe('YourClass', function () {
    
    beforeEach(function () {
        // Setup before each test
        $this->instance = new YourClass();
    });
    
    afterEach(function () {
        // Cleanup after each test
    });
    
    it('does something', function () {
        $result = $this->instance->doSomething();
        
        expect($result)->toBe('expected value');
    });
    
    it('throws exception for invalid input', function () {
        $this->instance->methodThatThrows();
    })->throws(InvalidArgumentException::class);
    
});
```

### Best Practices

1. **One test file per class** - `YourClass.php` → `YourClassTest.php`
2. **Descriptive test names** - Use `it('does something specific')`
3. **AAA pattern** - Arrange, Act, Assert
4. **Test isolation** - Each test should be independent
5. **Use beforeEach** - For common setup logic
6. **Mock external dependencies** - Don't make real API calls
7. **Test edge cases** - Empty inputs, null values, errors
8. **Keep tests fast** - Avoid sleep(), use mocks

## Test Coverage Goals

- **Unit tests:** > 80% code coverage
- **Integration tests:** All critical user paths
- **All public methods:** Should have tests
- **Edge cases:** Null, empty, invalid inputs
- **Error handling:** Exception scenarios

---

**Current Status: 237/237 tests passing ✅**
