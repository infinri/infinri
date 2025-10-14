# Infinri Framework

**A Complete, Test-Driven PHP MVC Framework**

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-237%20passing-brightgreen)](tests/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-production%20ready-brightgreen)](https://github.com/infinri/infinri)

---

## Overview

**Infinri** is a complete, production-ready MVC framework built with Test-Driven Development. Featuring a modular architecture inspired by Magento, it provides everything needed to build modern web applications with 237 passing tests covering all core functionality.

### Key Features

- ✅ **Modular Architecture** - Module system with dependency resolution
- ✅ **Dependency Injection** - PHP-DI 7.1 with autowiring
- ✅ **Configuration System** - XML-based with scope support
- ✅ **Layout System** - XML layouts with directives (remove, move, reference)
- ✅ **Template Engine** - PHTML with XSS protection
- ✅ **Routing** - URL pattern matching with parameters
- ✅ **HTTP Layer** - Request/Response wrappers
- ✅ **Database Layer** - Active Record & Repository patterns (PostgreSQL, MySQL, SQLite)
- ✅ **MVC Pattern** - Complete Model-View-Controller architecture
- ✅ **Test Coverage** - 237 tests covering all components

---

## Project Status

**Current Version:** 1.0.0-beta

**Framework Status:** ✅ Production Ready - All core components implemented and tested

### Completed (8 Phases)

- ✅ **Phase 1:** Component Registration (36 tests)
- ✅ **Phase 2:** Configuration System (25 tests)
- ✅ **Phase 3:** DI Container Integration (23 tests)
- ✅ **Phase 4:** Layout System (51 tests)
- ✅ **Phase 5:** Template & View System (18 tests)
- ✅ **Phase 6:** Routing & HTTP Layer (40 tests)
- ✅ **Phase 7:** End-to-End Integration (8 tests)
- ✅ **Phase 8:** Database Layer (36 tests)

**Total: 237 tests passing**

### Ready to Build

The framework core is complete! You can now:
- Build web applications
- Create RESTful APIs
- Develop custom modules
- Add business logic

See [`PROGRESS.md`](PROGRESS.md) for detailed development history.  
See [`RESUME_DEVELOPMENT.md`](RESUME_DEVELOPMENT.md) for setup instructions.

---

## Architecture

### Philosophy: Magento Patterns, Modern Tools

Infinri preserves Magento's battle-tested architectural patterns while replacing legacy components with modern, focused libraries:

| Concept | Magento Approach | Infinri Approach |
|---------|------------------|------------------|
| **Modularity** | ✅ Module system | ✅ Same pattern, simplified |
| **DI Container** | Custom implementation | **PHP-DI 7.1** |
| **Events** | Custom observer pattern | **Symfony EventDispatcher** |
| **Console** | Custom CLI | **Symfony Console** |
| **Routing** | Complex router | **nikic/fast-route** |
| **Cache** | Zend_Cache | **Symfony Cache** |
| **Frontend** | RequireJS + KnockoutJS | **Vanilla JS + LESS** |
| **Templating** | PHTML | **PHTML** (same) |
| **Layout System** | XML-based | **XML-based** (same) |

**Result:** The extensibility and structure of Magento with a fraction of the complexity.

### Module Structure

```
infinri/
├── app/
│   ├── Infinri/
│   │   ├── Core/              # Core Framework (foundation services)
│   │   ├── Theme/             # Base UI/UX theme
│   │   ├── Admin/             # Admin panel (planned)
│   │   ├── Customer/          # Customer management (planned)
│   │   ├── Content/           # CMS functionality (planned)
│   │   └── [CustomModules]/   # Your modules here
│   │
│   ├── etc/
│   │   ├── config.php         # Enabled modules list
│   │   ├── env.php            # Environment configuration
│   │   └── registration_globlist.php  # Module auto-discovery
│   │
│   ├── autoload.php           # Composer + module autoloading
│   └── bootstrap.php          # Application initialization
│
├── bin/
│   └── console                # CLI application entry point
│
├── pub/
│   ├── index.php              # Web application entry point
│   └── static/                # Published assets (generated)
│
├── var/
│   ├── cache/                 # Application cache
│   ├── log/                   # Application logs
│   ├── session/               # Session storage
│   └── tmp/                   # Temporary files
│
├── vendor/                    # Composer dependencies
├── node_modules/              # NPM dependencies
├── composer.json              # PHP dependencies
├── package.json               # Node.js build tools
└── README.md                  # This file
```

### Technology Stack

**Backend:**
- **PHP 8.1+** - Modern PHP with type safety
- **PostgreSQL/MySQL/SQLite** - Multi-database support
- **PHP-DI 7.1** - Dependency injection with autowiring
- **PDO** - Database abstraction layer

**Testing:**
- **Pest 3.x** - Modern testing framework  
- **PHPUnit** - Unit testing
- **PHPStan** - Static analysis

**Included:**
- ✅ Active Record pattern
- ✅ Repository pattern
- ✅ MVC architecture
- ✅ Template rendering (PHTML)
- ✅ URL routing
- ✅ Request/Response handling

---

## Getting Started

### Prerequisites

- **PHP:** 8.1 or higher
- **PostgreSQL:** 14+ (or MySQL 8+, or SQLite 3+)
- **Composer:** 2.x
- **PHP Extensions:** pdo, pdo_pgsql (or pdo_mysql/pdo_sqlite), mbstring, xml, json

### Quick Start

```bash
# 1. Clone/transfer project
cd /path/to/infinri

# 2. Install dependencies
composer install

# 3. Setup PostgreSQL (see DATABASE_SETUP.md for details)
sudo -u postgres psql -c "CREATE DATABASE infinri_test;"
sudo -u postgres psql -c "CREATE USER infinri WITH PASSWORD 'infinri';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE infinri_test TO infinri;"

# 4. Run tests to verify setup
composer test

# 5. Start development server
php -S localhost:8000 -t pub/
```

Visit: `http://localhost:8000/`

### Detailed Setup

For complete setup instructions on Ubuntu, see:
- **[RESUME_DEVELOPMENT.md](RESUME_DEVELOPMENT.md)** - Complete setup checklist
- **[DATABASE_SETUP.md](DATABASE_SETUP.md)** - Database configuration guide
- **[PROGRESS.md](PROGRESS.md)** - Development history and architecture

---

## Project Structure

### Core Modules

#### [Infinri_Core](app/Infinri/Core/)
The foundation framework providing essential services:
- Component registration and module management
- Configuration system (XML-based, scope-aware)
- Dependency injection container (PHP-DI)
- Layout system (XML-based page structure)
- Block and template rendering (PHTML)
- Asset management (LESS/JS compilation)
- Event system (Symfony EventDispatcher)
- Cache system (Symfony Cache)
- Routing and controllers
- Console commands

**Status:** 📝 Requirements defined, implementation in progress  
**See:** [app/Infinri/Core/README.md](app/Infinri/Core/README.md)

#### [Infinri_Theme](app/Infinri/Theme/)
Base presentation layer providing:
- Layout XML files (default, 1column, 2column, 3column)
- PHTML templates (header, footer, breadcrumb, pagination)
- LESS stylesheets (variables, grid, components)
- JavaScript components (navigation, modals, forms)
- ViewModels (Header, Footer, Breadcrumb)

**Status:** 📝 Requirements defined, awaiting Core implementation  
**See:** [app/Infinri/Theme/README.md](app/Infinri/Theme/README.md)

### Planned Modules

#### Infinri_Admin *(Planned)*
Administration panel:
- Admin authentication and authorization
- Grid component for data tables
- Form builder for CRUD operations
- Dashboard and widgets
- System configuration UI

#### Infinri_Content *(Planned)*
Content management:
- Pages (CMS pages with WYSIWYG)
- Blocks (reusable content snippets)
- Media library
- Page builder

#### Infinri_Customer *(Planned)*
Customer management:
- Registration and login
- Customer accounts
- Profile management
- Authentication system

#### Infinri_SEO *(Planned)*
Search engine optimization:
- Meta tags (title, description, keywords)
- OpenGraph and Twitter Cards
- Structured data (Schema.org JSON-LD)
- XML sitemaps
- robots.txt management

---

## Development

### Creating a New Module

```bash
# Module structure
app/Infinri/YourModule/
├── Api/                    # Service contracts (interfaces)
├── Block/                  # Block classes
├── Console/                # CLI commands
├── Controller/             # HTTP controllers
├── Model/                  # Business logic
├── Observer/               # Event observers
├── Plugin/                 # Interceptors
├── ViewModel/              # Presentation logic
├── etc/
│   ├── module.xml          # Module definition
│   ├── config.xml          # Default configuration
│   ├── di.xml              # DI configuration
│   └── events.xml          # Event subscriptions
├── view/
│   └── frontend/
│       ├── layout/         # Layout XML files
│       ├── templates/      # PHTML templates
│       └── web/
│           ├── css/        # LESS/CSS
│           └── js/         # JavaScript
└── registration.php        # Module registration
```

**registration.php:**
```php
<?php
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Infinri_YourModule',
    __DIR__
);
```

**etc/module.xml:**
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

**Enable module in app/etc/config.php:**
```php
return [
    'modules' => [
        'Infinri_Core' => 1,
        'Infinri_Theme' => 1,
        'Infinri_YourModule' => 1,
    ]
];
```

### CLI Commands

```bash
# Module management
php bin/console module:list                    # List all modules
php bin/console module:enable YourModule       # Enable a module
php bin/console module:disable YourModule      # Disable a module

# Cache management
php bin/console cache:clear                    # Clear all caches
php bin/console cache:clear --type=config      # Clear specific cache
php bin/console cache:status                   # Cache status

# Asset management
php bin/console asset:deploy                   # Deploy assets
php bin/console asset:deploy --area=frontend   # Deploy specific area
php bin/console asset:cache:clear              # Clear asset cache

# Configuration
php bin/console config:show                    # Show all config
php bin/console config:show section/group      # Show specific config
```

### Testing

```bash
# Run all tests
composer test

# Run unit tests only
vendor/bin/phpunit tests/Unit/

# Run integration tests
vendor/bin/phpunit tests/Integration/

# Run static analysis
composer phpstan

# Run code sniffer
composer lint
```

### Code Standards

- **PSR-12** - Coding style standard
- **PSR-4** - Autoloading standard
- **PHP 8.4** - Use modern features (enums, readonly properties, etc.)
- **Type hints** - Always use parameter and return types
- **Documentation** - PHPDoc for all public methods

---

## Configuration

### Environment Configuration (app/etc/env.php)

```php
<?php
return [
    // Development mode (disable caching, show errors)
    'dev_mode' => 1,
    
    // Database connection
    'db' => [
        'host' => 'localhost',
        'port' => 5432,
        'dbname' => 'infinri',
        'username' => 'infinri_user',
        'password' => 'secure_password',
    ],
    
    // Cache configuration
    'cache' => [
        'frontend' => [
            'default' => [
                'backend' => 'file',
                'backend_options' => [
                    'cache_dir' => __DIR__ . '/../../var/cache',
                ],
            ],
        ],
    ],
    
    // Session configuration
    'session' => [
        'save' => 'files',
        'save_path' => __DIR__ . '/../../var/session',
    ],
    
    // Encryption key (generate with: bin/console setup:generate-key)
    'crypt' => [
        'key' => 'your-encryption-key-here',
    ],
];
```

### Module Configuration (app/etc/config.php)

```php
<?php
return [
    'modules' => [
        'Infinri_Core' => 1,        // 1 = enabled, 0 = disabled
        'Infinri_Theme' => 1,
        // Add your modules here
    ],
];
```

---

## Performance

### Benchmarks (Target)

- **Framework overhead:** < 100ms
- **Page load (cached):** < 200ms
- **Page load (uncached):** < 500ms
- **Asset size (CSS):** < 50KB (minified + gzipped)
- **Asset size (JS):** < 30KB (minified + gzipped)

### Optimization Features

- ✅ **OPcache** - PHP bytecode caching
- ✅ **Configuration cache** - Merged XML cached
- ✅ **Layout cache** - Processed layouts cached
- ✅ **Block HTML cache** - Per-block output caching
- ✅ **Asset minification** - CSS/JS minified in production
- ✅ **Asset versioning** - Cache busting via timestamps
- ⏳ **Redis support** - For distributed caching
- ⏳ **CDN support** - For static assets
- ⏳ **HTTP/2 push** - Critical resource hints

---

## Deployment

### Production Checklist

```bash
# 1. Disable development mode
# Edit app/etc/env.php: 'dev_mode' => 0

# 2. Clear all caches
php bin/console cache:clear

# 3. Compile and minify assets
npm run build:prod

# 4. Deploy assets to pub/static
php bin/console asset:deploy

# 5. Run database migrations (when available)
php bin/console setup:upgrade

# 6. Set proper permissions
chmod -R 755 pub/
chmod -R 777 var/

# 7. Configure web server (Nginx/Apache)
# Point document root to: /path/to/infinri/pub/

# 8. Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Web Server Configuration

**Nginx:**
```nginx
server {
    listen 80;
    server_name infinri.local;
    root /path/to/infinri/pub;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /static/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Apache (.htaccess in pub/):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
```

---

## Contributing

We welcome contributions! Please follow these guidelines:

### Contribution Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Follow code standards (PSR-12, type hints, PHPDoc)
4. Write tests for new functionality
5. Ensure all tests pass (`composer test`)
6. Run static analysis (`composer phpstan`)
7. Commit changes with clear messages
8. Push to your fork
9. Open a Pull Request

### Code Review Process

- All PRs require review from maintainers
- Tests must pass (PHPUnit + PHPStan)
- Code coverage should not decrease
- Breaking changes require discussion

### Areas for Contribution

- 🐛 **Bug fixes** - Always welcome
- 📝 **Documentation** - Improve README, add examples
- ✨ **New features** - Discuss in Issues first
- 🎨 **Theme components** - New UI patterns
- 🔌 **Modules** - Build extension modules
- 🧪 **Tests** - Improve coverage
- 🌍 **Translations** - i18n support

---

## Roadmap

### Phase 1: Foundation (Q1 2025) - Current
- ✅ Project structure
- ✅ Core requirements definition
- ✅ Theme requirements definition
- ⏳ Core Framework implementation
- ⏳ Bootstrap and entry points
- ⏳ Theme module implementation

### Phase 2: Core Features (Q2 2025)
- 🔲 Admin module
- 🔲 Content management module
- 🔲 Customer module
- 🔲 Database migrations
- 🔲 Authentication/authorization

### Phase 3: Enhancement (Q3 2025)
- 🔲 SEO module
- 🔲 Email module
- 🔲 API framework (REST/GraphQL)
- 🔲 Search functionality
- 🔲 Media management

### Phase 4: Optimization (Q4 2025)
- 🔲 Performance module
- 🔲 CDN integration
- 🔲 Redis caching
- 🔲 Full-page caching
- 🔲 Service worker/PWA

### Version 1.0.0 (Target: Q4 2025)
- 🔲 Stable API
- 🔲 Complete documentation
- 🔲 Production-ready
- 🔲 Performance benchmarks
- 🔲 Security audit

---

## License

Infinri is open-source software licensed under the [MIT License](LICENSE).

```
MIT License

Copyright (c) 2025 Infinri

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## Support & Community

- **Documentation:** [https://docs.infinri.com](https://docs.infinri.com) *(Coming Soon)*
- **GitHub Issues:** [Report bugs or request features](https://github.com/infinri/infinri/issues)
- **GitHub Discussions:** [Ask questions, share ideas](https://github.com/infinri/infinri/discussions)
- **Email:** hello@infinri.com

---

## Acknowledgments

- **Magento** - For pioneering modular PHP architecture
- **Symfony** - For excellent standalone components
- **PHP-FIG** - For PSR standards
- **The PHP community** - For continuous innovation

---

**Built with ❤️ by the Infinri team**

---

*Last updated: 2025-10-14*
