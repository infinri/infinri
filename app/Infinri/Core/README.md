# Infinri Core Framework

**Version:** 1.0.0  
**License:** MIT  
**PHP Version:** ^8.1

---

## Overview

The **Infinri Core Framework** provides the foundational infrastructure for building modular, extensible PHP applications. Inspired by Magento's proven architecture and powered by modern PHP libraries, Core delivers enterprise-grade services for configuration, dependency injection, layouts, templating, asset management, caching, and more.

Core Framework contains **no business logic** or UI components—it's pure infrastructure designed to enable rapid development of feature-rich modules.

---

## Key Features

### Component System
- **Module Registration** - Auto-discovery and dependency resolution
- **Multi-Type Components** - Support for modules, themes, libraries, language packs
- **Dependency Management** - Module sequence and load ordering

### Configuration
- **XML-Based Config** - Merge configurations from all modules
- **Scope Support** - Default, store, and website-level configuration
- **Environment Config** - Separate settings for dev/staging/production
- **Configuration Cache** - Fast access to merged settings

### Dependency Injection
- **PHP-DI Integration** - Industry-standard DI container
- **XML Configuration** - Define preferences and arguments in di.xml
- **Plugin System (AOP)** - Method interception with before/around/after
- **Virtual Types** - Create variations without new classes

### Layout & Templating
- **XML Layouts** - Define page structure declaratively
- **Block Hierarchy** - Nested containers and blocks
- **PHTML Templates** - PHP-based template rendering
- **Template Fallback** - Module → Theme resolution chain
- **ViewModel Pattern** - Separate presentation logic

### Asset Management
- **LESS Compilation** - Automatic CSS generation
- **JS Minification** - Terser-based optimization
- **Asset Merging** - Combine and optimize resources
- **Source Maps** - Debug compiled assets
- **Cache Busting** - Version-based URLs

### Event System
- **Observer Pattern** - Decouple modules via events
- **Symfony EventDispatcher** - Battle-tested event handling
- **20+ Core Events** - Hook into framework lifecycle
- **Priority Support** - Control observer execution order

### Caching
- **Multi-Backend** - File, Redis, Memcached, APCu
- **Cache Types** - Separate pools for config, layout, blocks, etc.
- **PSR-6/PSR-16** - Standard cache interfaces
- **Tag-Based Invalidation** - Smart cache clearing

### Routing & HTTP
- **Fast-Route** - High-performance URL routing
- **Controller Dispatch** - Route → Controller → Action pattern
- **Multiple Response Types** - HTML, JSON, Redirect, Forward
- **Parameter Extraction** - Clean URL parsing

### Console Commands
- **Symfony Console** - Professional CLI interface
- **Auto-Discovery** - Commands from all modules
- **Built-in Commands** - Cache, modules, configuration management

### Internationalization
- **Translation System** - Multi-language support
- **Locale Management** - Switch languages dynamically
- **Pluralization** - Handle singular/plural forms
- **File-Based Translations** - PHP or CSV format

### Helper Utilities
- **Data Helper** - Array/string manipulation, formatting
- **Escaper Helper** - XSS protection, output sanitization
- **URL Helper** - Route-based URL generation
- **Translation Helper** - i18n functions

### Plugin Manager
- **Plugin System** - Extend any public method
- **Plugin Registration** - Register plugins in di.xml
- **Plugin Execution** - Before, around, and after original method

---

## Architecture

### Module Structure

```
app/Infinri/Core/
├── Api/                           # Service contracts (interfaces)
│   ├── AssetRepositoryInterface.php
│   ├── CacheInterface.php
│   ├── ComponentRegistrarInterface.php
│   ├── ConfigInterface.php
│   ├── ObserverInterface.php
│   └── RepositoryInterface.php
│
├── App/                           # Application components
│   ├── FrontController.php
│   ├── Request.php
│   ├── Response.php
│   └── Router.php
│
├── Block/                         # Base block classes
│   ├── AbstractBlock.php
│   ├── Template.php
│   ├── Text.php
│   └── Container.php
│
├── Console/                       # CLI commands
│   ├── CommandLoader.php
│   └── Command/
│       ├── CacheClearCommand.php
│       ├── CacheStatusCommand.php
│       ├── ModuleListCommand.php
│       └── ModuleStatusCommand.php
│
├── Controller/                    # Base controller classes
│   ├── AbstractAction.php
│   ├── AbstractController.php
│   └── Result/
│       ├── Forward.php
│       ├── Json.php
│       └── Redirect.php
│
├── Helper/                        # Utility helpers
│   ├── Data.php
│   ├── Escaper.php
│   ├── Translation.php
│   └── Url.php
│
├── Model/                         # Core services
│   ├── AbstractModel.php
│   ├── ComponentRegistrar.php
│   ├── ObjectManager.php
│   │
│   ├── Asset/
│   │   ├── Builder.php            # LESS/CSS/JS compilation
│   │   ├── Publisher.php          # Deploy to pub/static
│   │   ├── Repository.php         # Asset registration
│   │   └── UrlGenerator.php       # Asset URLs
│   │
│   ├── Cache/
│   │   ├── Factory.php            # Create cache pools
│   │   ├── Pool.php               # PSR-6/16 implementation
│   │   └── TypeList.php           # Cache type management
│   │
│   ├── Config/
│   │   ├── Loader.php             # Load module configs
│   │   ├── Reader.php             # Parse XML configs
│   │   └── ScopeConfig.php        # Access configuration
│   │
│   ├── Di/
│   │   ├── ContainerFactory.php   # PHP-DI setup
│   │   ├── PluginManager.php      # AOP interceptors
│   │   ├── XmlReader.php          # Parse di.xml
│   │   └── Plugin/
│   │       └── InterceptorInterface.php
│   │
│   ├── Event/
│   │   ├── Manager.php            # Event dispatcher
│   │   └── Config/Reader.php      # Parse events.xml
│   │
│   ├── Layout/
│   │   ├── Builder.php            # Create block instances
│   │   ├── Loader.php             # Load layout files
│   │   ├── Merger.php             # Merge XML layouts
│   │   ├── Processor.php          # Process directives
│   │   └── Renderer.php           # Render blocks
│   │
│   ├── Module/
│   │   ├── ModuleList.php         # Registered modules
│   │   ├── ModuleManager.php      # Enable/disable modules
│   │   └── ModuleReader.php       # Parse module.xml
│   │
│   ├── ResourceModel/
│   │   ├── AbstractResource.php   # Base resource model
│   │   └── Connection.php         # Database connections
│   │
│   ├── Url/
│   │   └── Builder.php            # URL generation
│   │
│   └── View/
│       ├── Engine.php             # Template rendering
│       └── TemplateResolver.php   # Template resolution
│
├── etc/
│   ├── config.xml                 # Default configuration
│   ├── di.xml                     # DI definitions
│   ├── events.xml                 # Event subscriptions
│   └── module.xml                 # Module metadata
│
├── registration.php               # Module registration
├── requirements.txt               # Implementation checklist
└── README.md                      # This file
```

---

## Installation

### Prerequisites

- **PHP:** 8.1 or higher
- **Composer:** 2.x
- **Node.js:** 18+ (for asset compilation)
- **Database:** PostgreSQL 14+, MySQL 8+, or SQLite 3

### Setup

Core Framework is installed as part of the Infinri project:

```bash
# Install dependencies
composer install
npm install

# Verify Core is enabled
# In app/etc/config.php:
return [
    'modules' => [
        'Infinri_Core' => 1,
    ]
];
```

---

## Usage

### Creating a Module

**1. Registration file** - `app/Infinri/YourModule/registration.php`:
```php
<?php
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Infinri_YourModule',
    __DIR__
);
```

**2. Module definition** - `app/Infinri/YourModule/etc/module.xml`:
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <module name="Infinri_YourModule" setup_version="1.0.0">
        <sequence>
            <module name="Infinri_Core"/>
        </sequence>
    </module>
</config>
```

**3. Enable module** - In `app/etc/config.php`:
```php
return [
    'modules' => [
        'Infinri_Core' => 1,
        'Infinri_YourModule' => 1,
    ]
];
```

### Configuration System

**Define config** - `app/Infinri/YourModule/etc/config.xml`:
```xml
<?xml version="1.0"?>
<config>
    <default>
        <your_section>
            <your_group>
                <enabled>1</enabled>
                <api_key>default_key</api_key>
            </your_group>
        </your_section>
    </default>
</config>
```

**Access config**:
```php
use Infinri\Core\Model\Config\ScopeConfig;

class YourService
{
    public function __construct(
        private ScopeConfig $scopeConfig
    ) {}
    
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'your_section/your_group/enabled'
        );
    }
}
```

### Dependency Injection

**Configure DI** - `app/Infinri/YourModule/etc/di.xml`:
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <!-- Interface preference -->
    <preference for="Your\InterfaceName" type="Your\Implementation"/>
    
    <!-- Constructor arguments -->
    <type name="Your\ClassName">
        <arguments>
            <argument name="config" xsi:type="string">value</argument>
            <argument name="service" xsi:type="object">ServiceClass</argument>
        </arguments>
    </type>
</config>
```

### Plugin System (AOP)

**Register plugin** - In `etc/di.xml`:
```xml
<type name="Infinri\Core\Model\User">
    <plugin name="user_logger" type="YourModule\Plugin\UserLogger" sortOrder="10"/>
</type>
```

**Create plugin**:
```php
namespace YourModule\Plugin;

use Infinri\Core\Model\Di\Plugin\InterceptorInterface;

class UserLogger implements InterceptorInterface
{
    // Runs before the original method
    public function beforeSave($subject, array $data): array
    {
        error_log("Saving user: " . json_encode($data));
        return [$data]; // Modified arguments
    }
    
    // Runs after the original method
    public function afterSave($subject, $result, array $data)
    {
        error_log("User saved successfully");
        return $result; // Can modify result
    }
    
    // Wraps the original method
    public function aroundSave($subject, callable $proceed, array $data)
    {
        // Before
        $startTime = microtime(true);
        
        // Execute original
        $result = $proceed($data);
        
        // After
        $duration = microtime(true) - $startTime;
        error_log("Save took {$duration}s");
        
        return $result;
    }
}
```

### Layout XML

**Create layout** - `app/Infinri/YourModule/view/frontend/layout/yourmodule_index_index.xml`:
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <update handle="1column"/>
    
    <referenceContainer name="content">
        <block class="Infinri\Core\Block\Template" 
               name="yourmodule.content" 
               template="Infinri_YourModule::content.phtml">
            <arguments>
                <argument name="title" xsi:type="string">Page Title</argument>
                <argument name="view_model" xsi:type="object">
                    Infinri\YourModule\ViewModel\Content
                </argument>
            </arguments>
        </block>
    </referenceContainer>
</layout>
```

### Templates

**Create template** - `app/Infinri/YourModule/view/frontend/templates/content.phtml`:
```php
<?php
/** @var \Infinri\Core\Block\Template $block */
$viewModel = $block->getViewModel();
$title = $block->getData('title');
?>
<div class="content">
    <h1><?= $block->escapeHtml($title) ?></h1>
    <p><?= $block->escapeHtml($viewModel->getDescription()) ?></p>
    <?= $block->getChildHtml() ?>
</div>
```

### Event Observers

**Register observer** - `app/Infinri/YourModule/etc/events.xml`:
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <event name="model_save_after">
        <observer name="your_observer" 
                  instance="Infinri\YourModule\Observer\ModelSaveObserver"/>
    </event>
</config>
```

**Create observer**:
```php
namespace Infinri\YourModule\Observer;

use Infinri\Core\Api\ObserverInterface;

class ModelSaveObserver implements ObserverInterface
{
    public function execute(array $data): void
    {
        $model = $data['object'] ?? null;
        // Your logic here
    }
}
```

### Translation (i18n)

**Add translations** - `app/Infinri/YourModule/i18n/fr_FR.php`:
```php
<?php
return [
    'Welcome' => 'Bienvenue',
    'Hello %s' => 'Bonjour %s',
    'item' => 'élément',
    'items' => 'éléments',
];
```

**Use in code**:
```php
use Infinri\Core\Helper\Translation;

class YourClass
{
    public function __construct(
        private Translation $translation
    ) {
        $this->translation->setLocale('fr_FR');
        $this->translation->loadTranslationFile(
            'fr_FR', 
            __DIR__ . '/../i18n/fr_FR.php'
        );
    }
    
    public function getMessage(string $name): string
    {
        return $this->translation->__('Hello %s', $name);
    }
}
```

---

## CLI Commands

```bash
# Module management
php bin/console module:list                    # List all modules
php bin/console module:status YourModule       # Check module status

# Cache management
php bin/console cache:clear                    # Clear all caches
php bin/console cache:clear --type=config      # Clear specific type
php bin/console cache:status                   # Show cache information

# Development
php -S localhost:8000 -t pub/                  # Start dev server
```

---

## Performance

### Caching Strategy

Core Framework uses multi-layer caching:

1. **OPcache** - PHP bytecode (enable in php.ini)
2. **Config Cache** - Merged XML configurations
3. **Layout Cache** - Processed layout structures
4. **Block HTML Cache** - Rendered block output
5. **Asset Cache** - Compiled CSS/JS files

**Recommended backends**:
- **Development**: Filesystem
- **Production**: Redis or Memcached
- **Single Server**: APCu

### Cache Configuration

In `app/etc/env.php`:
```php
return [
    'cache' => [
        'frontend' => [
            'default' => [
                'backend' => 'redis',
                'backend_options' => [
                    'server' => '127.0.0.1',
                    'port' => 6379,
                ],
            ],
        ],
    ],
];
```

---

## Testing

Core Framework includes comprehensive test coverage:

```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/pest tests/Unit/
vendor/bin/pest tests/Integration/

# With coverage
vendor/bin/pest --coverage
```

**Test Structure**:
- `tests/Unit/` - Isolated component tests
- `tests/Integration/` - Multi-component tests
- `tests/Pest.php` - Test configuration

---

## Core Events

Framework emits events at key lifecycle points:

| Event | When | Data |
|-------|------|------|
| `module_load_before` | Before loading modules | `modules` |
| `module_load_after` | After loading modules | `modules` |
| `config_load_before` | Before loading config | `scope` |
| `config_load_after` | After loading config | `config` |
| `layout_load_before` | Before loading layout | `handle` |
| `layout_generate_blocks_before` | Before generating blocks | `layout` |
| `layout_generate_blocks_after` | After generating blocks | `layout` |
| `block_html_before` | Before rendering block | `block` |
| `block_html_after` | After rendering block | `block`, `html` |
| `controller_dispatch_before` | Before controller exec | `controller` |
| `controller_dispatch_after` | After controller exec | `controller`, `result` |
| `model_save_before` | Before model save | `object` |
| `model_save_after` | After model save | `object` |
| `model_delete_before` | Before model delete | `object` |
| `model_delete_after` | After model delete | `object` |
| `model_load_after` | After model load | `object` |
| `asset_publish_before` | Before publishing assets | `module`, `area` |
| `asset_publish_after` | After publishing assets | `module`, `area` |
| `request_before` | Before processing request | `request` |
| `response_send_before` | Before sending response | `response` |

---

## Dependencies

### PHP Packages (Composer)

- **php-di/php-di** ^7.1 - Dependency injection
- **symfony/event-dispatcher** ^7.3 - Event system
- **symfony/cache** ^7.3 - Caching
- **symfony/console** ^7.3 - CLI commands
- **nikic/fast-route** ^1.3 - Routing
- **monolog/monolog** ^3.9 - Logging
- **vlucas/phpdotenv** ^5.6 - Environment config

### Node Packages (NPM)

- **less** ^4.4.2 - LESS compilation
- **clean-css-cli** ^5.6.3 - CSS minification
- **terser** ^5.44.0 - JS minification
- **chokidar-cli** ^3.0.0 - File watching

---

## Best Practices

### Module Development

1. **Keep modules focused** - One responsibility per module
2. **Use DI** - Inject dependencies, don't create them
3. **Leverage events** - Decouple modules via observer pattern
4. **Cache expensive operations** - Use cache types appropriately
5. **Follow naming conventions** - Infinri_ModuleName format

### Performance

1. **Enable caching in production** - All cache types
2. **Use Redis/Memcached** - Faster than filesystem
3. **Minimize layout complexity** - Fewer blocks = faster rendering
4. **Optimize assets** - Minify and merge CSS/JS
5. **Use OPcache** - Always in production

### Security

1. **Escape output** - Always use `$block->escapeHtml()`
2. **Validate input** - Use `Escaper` helper methods
3. **Sanitize data** - `sanitizeInt()`, `sanitizeUrl()`, etc.
4. **Use HTTPS** - Especially for production
5. **Keep dependencies updated** - Run `composer update` regularly

---

## License

MIT License - See LICENSE file for details

---

## Contributing

Contributions are welcome! Please follow PSR-12 coding standards and include tests for new features.

---

*Infinri Core Framework - Modern PHP infrastructure for modular applications*
