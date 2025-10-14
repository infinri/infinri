# Infinri Test Suite

## Overview

Infinri uses **Pest** for testing - a delightful PHP testing framework with focus on simplicity.

## Running Tests

### All Tests
```bash
composer test
# or
vendor/bin/pest
```

### Specific Test Suite
```bash
vendor/bin/pest tests/Unit
vendor/bin/pest tests/Integration
```

### Specific Test File
```bash
vendor/bin/pest tests/Unit/ComponentRegistrarTest.php
```

### With Coverage
```bash
vendor/bin/pest --coverage
vendor/bin/pest --coverage --min=80
```

### Watch Mode (runs on file change)
```bash
vendor/bin/pest --watch
```

## Test Structure

```
tests/
├── Pest.php                      # Pest configuration
├── Unit/                         # Unit tests
│   ├── ComponentRegistrarTest.php
│   └── Module/
│       ├── ModuleReaderTest.php
│       ├── ModuleListTest.php
│       └── ModuleManagerTest.php
└── Integration/                  # Integration tests (coming soon)
```

## Writing Tests

### Basic Test
```php
it('can do something', function () {
    expect(true)->toBeTrue();
});
```

### Test with Setup
```php
beforeEach(function () {
    $this->service = new MyService();
});

it('can use the service', function () {
    expect($this->service)->toBeInstanceOf(MyService::class);
});
```

### Grouped Tests
```php
describe('MyClass', function () {
    
    it('does something', function () {
        // Test code
    });
    
    it('does something else', function () {
        // Test code
    });
    
});
```

### Testing Exceptions
```php
it('throws an exception', function () {
    throw new Exception('Error');
})->throws(Exception::class, 'Error');
```

## Current Test Coverage

### Phase 1: Component Registration System ✅

**ComponentRegistrarTest.php** (13 tests)
- ✅ Singleton pattern
- ✅ Interface implementation
- ✅ Module registration
- ✅ Multiple component types
- ✅ Path retrieval
- ✅ Invalid type handling
- ✅ Path validation
- ✅ Path normalization
- ✅ Non-existent component handling
- ✅ Empty component type handling
- ✅ Clone prevention
- ✅ Unserialization prevention

**ModuleReaderTest.php** (6 tests)
- ✅ Read Infinri_Core module.xml
- ✅ Read Infinri_Theme module.xml
- ✅ Handle missing module.xml
- ✅ Validate module.xml exists
- ✅ Validate fails for invalid path
- ✅ Handle malformed XML gracefully

**ModuleListTest.php** (8 tests)
- ✅ Get all registered modules
- ✅ Include module paths
- ✅ Get all module names
- ✅ Get single module
- ✅ Handle non-existent module
- ✅ Check if module exists
- ✅ Cache module data
- ✅ Clear cache

**ModuleManagerTest.php** (9 tests)
- ✅ Check if module is enabled
- ✅ Handle non-existent module
- ✅ Get all enabled modules
- ✅ Get enabled module names only
- ✅ Return modules in dependency order
- ✅ Handle modules with no dependencies
- ✅ Handle circular dependencies gracefully
- ✅ Clear cache
- ✅ Load from app/etc/config.php

**Total: 36 tests covering Phase 1**

## Upcoming Tests

### Phase 2: Configuration System
- ConfigReaderTest
- ConfigLoaderTest
- ScopeConfigTest
- ConfigCacheTest

### Phase 3: DI Container
- ContainerFactoryTest
- XmlReaderTest
- PluginManagerTest

### Phase 4: Layout System
- LayoutLoaderTest
- LayoutMergerTest
- LayoutProcessorTest
- LayoutRendererTest

## Best Practices

1. **One assertion per test** - Keep tests focused
2. **Descriptive test names** - Use natural language
3. **Arrange-Act-Assert** - Structure tests clearly
4. **Test behavior, not implementation** - Focus on what, not how
5. **Clean up after tests** - Reset state, delete temp files
6. **Use beforeEach/afterEach** - Share setup/teardown logic
7. **Mock external dependencies** - Keep tests fast and isolated

## Continuous Integration

Tests are automatically run on:
- Every commit (pre-commit hook)
- Every pull request (GitHub Actions)
- Before deployment (CI/CD pipeline)

## Performance

Target test execution time:
- **Unit tests:** < 1 second per test
- **Integration tests:** < 5 seconds per test
- **Full suite:** < 30 seconds total

Current performance: ⏱️ Run `vendor/bin/pest` to see timing
