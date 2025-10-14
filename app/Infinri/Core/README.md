# Infinri Core Framework

**Version:** 0.1.0  
**License:** MIT  
**PHP Version:** ^8.4

---

## Overview

The **Infinri Core Framework** is the foundational module that powers the entire Infinri platform. It provides essential infrastructure services that all other modules depend on, implementing a proven, Magento-inspired modular architecture with modern PHP best practices.

Core Framework is pure infrastructure—it contains **no business logic**, **no UI components**, and **no database models**. Its sole responsibility is to provide robust, performant services that enable rapid development of feature modules.

---

## Philosophy

### Magento-Inspired, Modernized

Infinri Core preserves the battle-tested architectural patterns from Magento while replacing legacy components with modern, focused libraries:

**Keep:**
- ✅ Modular architecture (registration.php, etc/module.xml)
- ✅ Layout XML system (containers, blocks, handles)
- ✅ Dependency injection configuration (etc/di.xml)
- ✅ Event/Observer pattern (etc/events.xml)
- ✅ Plugin/Interceptor pattern (AOP)
- ✅ Multi-area support (base, frontend, adminhtml)
- ✅ Template fallback mechanism
- ✅ Asset merging and compilation

**Replace:**
- ❌ Magento's custom DI → **PHP-DI 7.1** (industry standard)
- ❌ Custom event system → **Symfony EventDispatcher 7.3**
- ❌ Zend_Cache → **Symfony Cache 7.3**
- ❌ Custom CLI → **Symfony Console 7.3**
- ❌ Complex routing → **nikic/fast-route 1.3**
- ❌ PHP-based LESS compilation → **Node.js LESS 4.4**
- ❌ RequireJS complexity → **Plain JavaScript + Terser 5.44**

**Result:** The modularity and extensibility of Magento without the bloat.

---

## Core Responsibilities

### What Core Framework Provides

#### 1. **Component Registration System**
- Module discovery and registration
- Dependency resolution (module sequence)
- Module enable/disable management
- Support for modules, themes, libraries, and language packs

#### 2. **Configuration System**
- XML-based configuration (etc/config.xml)
- Scope-based config (default/store/website)
- Configuration merging from all modules
- Environment-specific settings (app/etc/env.php)
- Configuration caching

#### 3. **Dependency Injection Container**
- PHP-DI integration with XML configuration
- Interface → Implementation preferences
- Constructor argument injection
- Virtual types and factories
- Plugin/Interceptor support (AOP)

#### 4. **Layout System**
- XML-based page structure definition
- Multi-module layout merging
- Container and block hierarchy
- Layout handles (route-based, custom)
- Area-specific layouts (base, frontend, adminhtml)
- Layout caching

#### 5. **Block System**
- Base block classes (AbstractBlock, Template, Container)
- Template rendering (PHTML)
- Child block management
- Block caching (HTML cache)
- Output escaping utilities

#### 6. **Template Engine**
- PHTML template rendering
- Template resolution with fallback chain
- Block context injection ($block variable)
- ViewModel integration
- Automatic output escaping

#### 7. **Asset Management**
- CSS/JS registration and ordering
- LESS → CSS compilation (via Node.js)
- JavaScript minification (via Terser)
- Asset publishing to pub/static/
- Source map generation
- Version-based cache busting

#### 8. **Event System**
- Symfony EventDispatcher integration
- Observer pattern (events.xml)
- Core framework events
- Event data passing
- Disabled observer support

#### 9. **Cache System**
- Symfony Cache integration
- Multiple cache backends (file, Redis, Memcached, APCu)
- Cache type management (config, layout, block_html)
- Tag-based cache invalidation
- Cache warming and clearing

#### 10. **Routing System**
- Fast-route integration
- URL → Controller/Action mapping
- Route parameter extraction
- URL generation (route → URL)

#### 11. **HTTP Request/Response**
- PSR-7 compatible (optional)
- Request object abstraction
- Response types (HTML, JSON, Redirect, Forward)

#### 12. **Console Application**
- Symfony Console integration
- Command auto-discovery from modules
- Core commands (module, cache, config, asset)

---

## Architecture

### Module Structure

```
app/Infinri/Core/
├── Api/                           # Service contracts (interfaces)
│   ├── BlockInterface.php
│   ├── ComponentRegistrarInterface.php
│   ├── ConfigInterface.php
│   ├── LayoutInterface.php
│   └── ObserverInterface.php
│
├── Block/                         # Base block classes
│   ├── AbstractBlock.php
│   ├── Template.php
│   ├── Text.php
│   └── Container.php
│
├── Console/                       # CLI commands
│   └── Command/
│       ├── ModuleListCommand.php
│       ├── CacheClearCommand.php
│       ├── ConfigShowCommand.php
│       └── AssetDeployCommand.php
│
├── Controller/                    # Base controller classes
│   ├── AbstractAction.php
│   └── Result/
│       ├── Json.php
│       ├── Redirect.php
│       └── Forward.php
│
├── Helper/                        # Helper utilities (minimal)
│   ├── Url.php
│   ├── Config.php
│   └── Escaper.php
│
├── Model/                         # Core framework services
│   ├── ComponentRegistrar.php
│   ├── ModuleList.php
│   ├── ModuleLoader.php
│   ├── ObjectManager.php
│   ├── Application.php
│   │
│   ├── Config/
│   │   ├── Reader.php
│   │   ├── Loader.php
│   │   ├── ScopeConfig.php
│   │   └── Cache.php
│   │
│   ├── Di/
│   │   ├── ContainerFactory.php
│   │   ├── XmlReader.php
│   │   ├── PluginManager.php
│   │   └── Interceptor.php
│   │
│   ├── Layout/
│   │   ├── Loader.php
│   │   ├── Merger.php
│   │   ├── Processor.php
│   │   ├── Builder.php
│   │   ├── Renderer.php
│   │   └── Cache.php
│   │
│   ├── Template/
│   │   ├── Engine.php
│   │   └── Resolver.php
│   │
│   ├── Asset/
│   │   ├── Repository.php
│   │   ├── Builder.php
│   │   ├── Publisher.php
│   │   ├── UrlGenerator.php
│   │   └── Cache.php
│   │
│   ├── Event/
│   │   ├── Manager.php
│   │   ├── Observer.php
│   │   └── Config/Reader.php
│   │
│   ├── Cache/
│   │   ├── Factory.php
│   │   ├── Pool.php
│   │   └── TypeList.php
│   │
│   ├── Route/
│   │   └── Router.php
│   │
│   └── Url/
│       └── Builder.php
│
├── etc/
│   ├── module.xml                 # Core module definition
│   ├── config.xml                 # Default configuration
│   ├── di.xml                     # DI container definitions
│   └── events.xml                 # Event subscriptions
│
├── registration.php               # Module registration
├── requirements.txt               # Implementation checklist
└── README.md                      # This file
```

### Application Bootstrap Flow

```
1. pub/index.php
   └─> app/autoload.php
       └─> vendor/autoload.php (Composer)
       └─> app/etc/registration_globlist.php (auto-discover modules)
           └─> app/Infinri/*/registration.php (register each module)

2. app/bootstrap.php
   └─> Load app/etc/env.php (database, cache config)
   └─> Initialize error handling
   └─> Create DI Container (PHP-DI)
       └─> Load etc/di.xml from all modules
   └─> Load module list (app/etc/config.php)
   └─> Initialize ComponentRegistrar

3. Application::run()
   └─> Route request (Fast-route)
   └─> Dispatch to controller
   └─> Load layout XML (handle-based)
   └─> Generate blocks
   └─> Render templates
   └─> Send response
```

---

## Installation & Setup

### Prerequisites

- **PHP:** 8.4 or higher
- **Composer:** 2.x
- **Node.js:** 18+ (for asset compilation)
- **PostgreSQL:** 14+ (for database-backed features)

### Installing Core Framework

Core Framework is part of the Infinri project and is installed automatically via the root `composer.json`.

```bash
# Install all dependencies
composer install
npm install

# Enable Core module (if not already enabled)
# Edit app/etc/config.php:
return [
    'modules' => [
        'Infinri_Core' => 1,
    ]
];
```

### Bootstrap Configuration

Create application bootstrap files (these are provided by Core Framework):

**app/autoload.php:**
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/etc/registration_globlist.php';
```

**app/bootstrap.php:**
```php
<?php
require __DIR__ . '/autoload.php';

// Load environment configuration
$env = require __DIR__ . '/etc/env.php';

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', $env['dev_mode'] ?? 0);

// Create and return DI container
return \Infinri\Core\Model\Di\ContainerFactory::create();
```

**app/etc/registration_globlist.php:**
```php
<?php
$registrationFiles = glob(__DIR__ . '/../Infinri/*/registration.php');
foreach ($registrationFiles as $file) {
    require $file;
}
```

---

## Usage

### For Module Developers

#### Registering a New Module

**app/Infinri/YourModule/registration.php:**
```php
<?php
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Infinri_YourModule',
    __DIR__
);
```

**app/Infinri/YourModule/etc/module.xml:**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <module name="Infinri_YourModule" setup_version="1.0.0">
        <sequence>
            <module name="Infinri_Core"/>
            <module name="Infinri_Theme"/>
        </sequence>
    </module>
</config>
```

Enable your module in **app/etc/config.php:**
```php
return [
    'modules' => [
        'Infinri_Core' => 1,
        'Infinri_Theme' => 1,
        'Infinri_YourModule' => 1,
    ]
];
```

#### Using Configuration System

**app/Infinri/YourModule/etc/config.xml:**
```xml
<?xml version="1.0"?>
<config>
    <default>
        <your_section>
            <your_group>
                <field_name>default_value</field_name>
            </your_group>
        </your_section>
    </default>
</config>
```

**In PHP:**
```php
use Infinri\Core\Model\Config\ScopeConfig;

class YourClass {
    public function __construct(
        private ScopeConfig $scopeConfig
    ) {}
    
    public function getValue(): string {
        return $this->scopeConfig->getValue('your_section/your_group/field_name');
    }
}
```

#### Using Dependency Injection

**app/Infinri/YourModule/etc/di.xml:**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <!-- Interface preference -->
    <preference for="YourInterface" type="YourImplementation"/>
    
    <!-- Constructor arguments -->
    <type name="YourClass">
        <arguments>
            <argument name="paramName" xsi:type="string">value</argument>
            <argument name="service" xsi:type="object">ServiceClass</argument>
        </arguments>
    </type>
    
    <!-- Plugin/Interceptor -->
    <type name="TargetClass">
        <plugin name="your_plugin" type="YourPlugin" sortOrder="10"/>
    </type>
</config>
```

#### Creating Layout XML

**app/Infinri/YourModule/view/frontend/layout/yourmodule_index_index.xml:**
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <update handle="1column"/>
    
    <referenceContainer name="content">
        <block class="Infinri\Core\Block\Template" name="yourmodule.content" template="Infinri_YourModule::content.phtml">
            <arguments>
                <argument name="view_model" xsi:type="object">Infinri\YourModule\ViewModel\Content</argument>
            </arguments>
        </block>
    </referenceContainer>
</layout>
```

#### Creating Templates

**app/Infinri/YourModule/view/frontend/templates/content.phtml:**
```php
<?php
/** @var \Infinri\Core\Block\Template $block */
/** @var \Infinri\YourModule\ViewModel\Content $viewModel */
$viewModel = $block->getViewModel();
?>
<div class="content">
    <h1><?= $block->escapeHtml($viewModel->getTitle()) ?></h1>
    <p><?= $block->escapeHtml($viewModel->getDescription()) ?></p>
    <?= $block->getChildHtml('additional_content') ?>
</div>
```

#### Registering Event Observers

**app/Infinri/YourModule/etc/events.xml:**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <event name="core_event_name">
        <observer name="your_observer" instance="Infinri\YourModule\Observer\YourObserver"/>
    </event>
</config>
```

**Observer class:**
```php
namespace Infinri\YourModule\Observer;

use Infinri\Core\Api\ObserverInterface;
use Symfony\Component\EventDispatcher\Event;

class YourObserver implements ObserverInterface
{
    public function execute(Event $event): void
    {
        $data = $event->getData();
        // Your logic here
    }
}
```

---

## CLI Commands

Core Framework provides essential CLI commands:

```bash
# Module management
php bin/console module:list                    # List all modules
php bin/console module:enable Infinri_Module   # Enable a module
php bin/console module:disable Infinri_Module  # Disable a module

# Cache management
php bin/console cache:clear                    # Clear all caches
php bin/console cache:clear --type=config      # Clear specific cache type
php bin/console cache:status                   # Show cache status

# Configuration
php bin/console config:show                    # Show all configuration
php bin/console config:show section/group      # Show specific path

# Asset management
php bin/console asset:deploy                   # Compile and deploy assets
php bin/console asset:deploy --area=frontend   # Deploy for specific area
php bin/console asset:cache:clear              # Clear asset cache
```

---

## Development Workflow

### Development Mode

In **development mode**, Core Framework:
- ✅ Disables all caching (layout, config, block HTML)
- ✅ Generates CSS/JS source maps
- ✅ Symlinks assets instead of copying
- ✅ Shows detailed error pages with stack traces
- ✅ Reloads configuration on every request

**Enable development mode:**
```php
// app/etc/env.php
return [
    'dev_mode' => 1,
    // ...
];
```

### Production Mode

In **production mode**, Core Framework:
- ✅ Enables all caching
- ✅ Minifies CSS/JS
- ✅ Copies assets (no symlinks)
- ✅ Shows user-friendly error pages
- ✅ Caches configuration

**Enable production mode:**
```php
// app/etc/env.php
return [
    'dev_mode' => 0,
    // ...
];
```

---

## Performance Considerations

### Caching Strategy

Core Framework implements multi-layer caching:

1. **OPcache** - PHP bytecode caching (always on in production)
2. **Configuration cache** - Merged XML cached after first load
3. **Layout cache** - Processed layout structures cached per handle
4. **Block HTML cache** - Individual block output cached (configurable)
5. **Asset cache** - Compiled CSS/JS cached until source changes

**Cache backends supported:**
- File-based (default, no setup required)
- Redis (recommended for production)
- Memcached
- APCu (for single-server deployments)

### Load Time Budget

Core Framework initialization target: **< 100ms**

- Module loading: < 10ms
- Configuration loading: < 20ms
- DI container setup: < 30ms
- Layout processing: < 40ms

**Measured in development mode with file cache and 10 modules.**

---

## Extensibility

### Plugin/Interceptor System

Extend any public method without modifying source code:

```xml
<type name="Infinri\Core\Model\Config\ScopeConfig">
    <plugin name="your_plugin" type="YourModule\Plugin\ScopeConfigPlugin"/>
</type>
```

```php
class ScopeConfigPlugin
{
    // Before original method
    public function beforeGetValue($subject, string $path)
    {
        // Modify arguments
        return [$path];
    }
    
    // After original method
    public function afterGetValue($subject, $result, string $path)
    {
        // Modify return value
        return strtoupper($result);
    }
    
    // Around original method (full control)
    public function aroundGetValue($subject, callable $proceed, string $path)
    {
        // Before
        $result = $proceed($path);
        // After
        return $result;
    }
}
```

### Event System

Emit and listen to events without tight coupling:

```php
// Dispatch event
$eventManager->dispatch('custom_event_name', [
    'data' => $someData,
    'object' => $someObject
]);
```

```xml
<!-- Subscribe to event -->
<event name="custom_event_name">
    <observer name="your_listener" instance="YourModule\Observer\CustomListener"/>
</event>
```

---

## Module Dependencies

### Required Composer Packages

```json
{
    "require": {
        "php": "^8.4",
        "php-di/php-di": "^7.1",
        "symfony/event-dispatcher": "^7.3",
        "symfony/cache": "^7.3",
        "symfony/console": "^7.3",
        "nikic/fast-route": "^1.3",
        "monolog/monolog": "^3.9"
    }
}
```

### Required NPM Packages

```json
{
    "devDependencies": {
        "less": "^4.4.2",
        "clean-css-cli": "^5.6.3",
        "terser": "^5.44.0",
        "chokidar-cli": "^3.0.0"
    }
}
```

---

## Testing

Core Framework follows rigorous testing standards:

### Unit Tests
```bash
vendor/bin/phpunit tests/Unit/
```

Tests for:
- ComponentRegistrar
- ModuleList/ModuleLoader
- Configuration system
- DI container
- Layout processing
- Block rendering
- Asset compilation

### Integration Tests
```bash
vendor/bin/phpunit tests/Integration/
```

Tests for:
- Module registration flow
- Layout XML merging
- Event dispatching
- Cache operations
- Full request/response cycle

### Static Analysis
```bash
vendor/bin/phpstan analyse
```

---

## Contributing

We welcome contributions to Core Framework! However, due to its critical nature, all changes must:

1. ✅ Maintain backward compatibility
2. ✅ Include comprehensive tests (unit + integration)
3. ✅ Follow PSR-12 coding standards
4. ✅ Update this README if adding new features
5. ✅ Not introduce business logic (infrastructure only)
6. ✅ Not add dependencies without discussion

**See:** [CONTRIBUTING.md](../../../CONTRIBUTING.md) for full guidelines.

---

## Versioning

Core Framework follows [Semantic Versioning 2.0.0](https://semver.org/):

- **MAJOR:** Breaking changes (interface changes, removal of methods)
- **MINOR:** New features (backward compatible)
- **PATCH:** Bug fixes (backward compatible)

**Current Version:** 0.1.0 (pre-release)

---

## Roadmap

### Version 0.1.0 (Current - Foundation)
- ✅ Component registration system
- ✅ Module discovery and loading
- ✅ Configuration system
- ✅ DI container integration
- ✅ Layout XML system
- ✅ Block and template rendering
- ✅ Asset management
- ✅ Event system
- ✅ Cache system
- ✅ Console commands

### Version 0.2.0 (Optimization)
- ⏳ Pre-compiled interceptors (no runtime proxy generation)
- ⏳ Advanced layout caching strategies
- ⏳ Asset bundling and HTTP/2 push
- ⏳ Critical CSS extraction
- ⏳ Database schema management (migrations via Phinx)

### Version 0.3.0 (Developer Experience)
- ⏳ Debug toolbar
- ⏳ Code generation commands (module scaffolding)
- ⏳ Hot module reload for development
- ⏳ GraphQL API support
- ⏳ REST API framework

### Version 1.0.0 (Stable Release)
- ⏳ Comprehensive documentation
- ⏳ 100% test coverage
- ⏳ Performance benchmarks
- ⏳ Production-ready cache warming
- ⏳ Multi-tenant support

---

## FAQ

### Why not use Laravel/Symfony/other framework?

We need Magento's modularity and extensibility patterns (layout XML, plugins, multi-module config merging) which don't exist in general-purpose frameworks. However, we **do** use Symfony components where appropriate (EventDispatcher, Console, Cache).

### Can I use Core Framework outside of Infinri?

Yes! Core Framework is designed to be reusable. As long as you follow the module structure conventions, you can build any application on top of it.

### How is this different from Magento 2?

**Same:** Architectural patterns, XML-based configuration, modular structure  
**Different:** Modern dependencies, no Zend Framework, no custom implementations of standard features, cleaner codebase, better performance

### Is this compatible with Magento modules?

No. While the concepts are similar, the implementations differ. However, porting Magento modules is straightforward due to architectural similarities.

### What's the performance impact?

Core Framework adds **< 100ms** overhead compared to a raw PHP application. This includes module discovery, DI container setup, configuration loading, and layout processing. With proper caching, subsequent requests are **< 10ms** overhead.

---

## License

Infinri Core Framework is open-source software licensed under the [MIT License](../../../LICENSE).

```
Copyright (c) 2025 Infinri

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
...
```

---

## Support

- **Documentation:** [https://docs.infinri.com](https://docs.infinri.com)
- **Issues:** [GitHub Issues](https://github.com/infinri/infinri/issues)
- **Discussions:** [GitHub Discussions](https://github.com/infinri/infinri/discussions)
- **Email:** core@infinri.com

---

## Credits

**Inspired by:** Magento 2 Framework  
**Built with:** PHP-DI, Symfony Components, Fast-route  
**Maintained by:** Infinri Core Team

---

**For technical implementation details, see [requirements.txt](requirements.txt).**
