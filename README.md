# Infinri Framework

**A Modular CMS Platform with Magento-Inspired Architecture**

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://www.php.net/)
[![Tests](https://img.shields.io/badge/tests-640%20passing-brightgreen)](tests/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Status](https://img.shields.io/badge/status-in%20development-yellow)](https://github.com/infinri/infinri)

---

## Overview

**Infinri** is a modular content management platform built with PHP 8.1+, featuring a Magento-inspired architecture. With 640 passing tests and a complete admin interface, it provides a solid foundation for building portfolio websites and content-driven applications.

### Key Features

**Core Framework:**
- ✅ **Modular Architecture** - Module system with dependency management
- ✅ **XML-Based Layouts** - Layout inheritance and composition
- ✅ **Template Engine** - PHTML templates with block system
- ✅ **Routing System** - Frontend and admin route separation
- ✅ **Configuration** - Database-backed config with scopes
- ✅ **Database Layer** - PDO with PostgreSQL, migrations, data patches
- ✅ **Error Handling** - Custom 404/500 pages with logging

**CMS Module:**
- ✅ **Pages & Blocks** - Full CRUD with UI Component grids/forms
- ✅ **Media Manager** - Folder organization, drag-drop upload, image picker
- ✅ **Widget System** - HTML, Block Reference, Image, Video widgets
- ✅ **SEO Support** - Meta tags, URL keys, homepage protection
- ✅ **Content Editor** - Integrated image picker with cursor insertion

**Admin Module:**
- ✅ **Dashboard** - Statistics and quick actions
- ✅ **Menu System** - XML-based, extensible navigation
- ✅ **Unified Layout** - Consistent admin interface across all pages
- ✅ **Module Injection** - Any module can add menu items

---

## Project Status

**Current Version:** 1.0.0-dev  
**Target:** Portfolio website CMS  
**Tests:** 640 passing (1298 assertions)

### Completed Modules

- ✅ **Infinri_Core** - Framework foundation
- ✅ **Infinri_Theme** - Frontend styling and layouts
- ✅ **Infinri_Cms** - Content management (pages, blocks, widgets)
- ✅ **Infinri_Admin** - Admin panel with dashboard and navigation

### Current Focus

Building a portfolio website with:
- Dynamic page management
- Image gallery with media manager
- Content blocks for reusable components
- SEO-optimized pages

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
│   │   ├── Core/              # Core framework (routing, layouts, DI)
│   │   ├── Theme/             # Frontend theme and styling
│   │   ├── Admin/             # Admin panel and dashboard
│   │   ├── Cms/               # Content management (pages, blocks)
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

For complete setup instructions, see module-specific README files listed above.

---

## Project Structure

### Implemented Modules

#### [Infinri_Core](app/Infinri/Core/)
Foundation framework:
- Module system with dependency resolution
- XML-based layout system with inheritance
- Template rendering engine (PHTML)
- Frontend and admin routing
- Database layer (PDO, migrations, data patches)
- Configuration system (database-backed)
- Media Manager (folder-based, upload, image picker)
- Error handling and logging

**Documentation:** [app/Infinri/Core/README.md](app/Infinri/Core/README.md)

#### [Infinri_Theme](app/Infinri/Theme/)
Frontend presentation layer:
- Layout XML files (base_default, default)
- PHTML templates (header, footer, messages)
- LESS stylesheets (responsive, modern)
- ViewModels for data presentation

**Documentation:** [app/Infinri/Theme/README.md](app/Infinri/Theme/README.md)

#### [Infinri_Cms](app/Infinri/Cms/)
Content management system:
- Pages with full CRUD (admin grids and forms)
- Blocks for reusable content
- Widget system (HTML, Block Reference, Image, Video)
- SEO fields (meta title, description, keywords)
- Homepage protection
- URL key management

**Documentation:** [app/Infinri/Cms/README.md](app/Infinri/Cms/README.md)

#### [Infinri_Admin](app/Infinri/Admin/)
Admin panel foundation:
- Dashboard with statistics
- XML-based menu system
- Extensible navigation (modules inject menu items)
- Unified admin layout (header, navigation, footer)
- Menu Builder with auto-discovery

**Documentation:** [app/Infinri/Admin/README.md](app/Infinri/Admin/README.md)

### Future Modules

- **Navigation** - Frontend menu system with show_in_nav flag
- **SEO** - URL rewrites and sitemap generation  
- **Customer** - User accounts and authentication (if needed)
- **Search** - Site-wide search functionality

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

### Phase 1: Foundation ✅ COMPLETE
- ✅ Core framework (layouts, routing, templates)
- ✅ Theme module (frontend styling)
- ✅ CMS module (pages, blocks, widgets)
- ✅ Admin module (dashboard, menu system)
- ✅ Media Manager (upload, folders, image picker)
- ✅ Database layer (migrations, data patches)

### Phase 2: Navigation & SEO (In Progress)
- ⏳ Frontend navigation system
- ⏳ URL rewrite system
- 🔲 Sitemap generation
- 🔲 Structured data (Schema.org)

### Phase 3: Enhancement (Future)
- 🔲 Frontend menu builder
- 🔲 Contact form module
- 🔲 Search functionality
- 🔲 Email notifications

### Phase 4: Optimization (Future)
- 🔲 Asset compilation pipeline
- 🔲 Cache system improvements
- 🔲 Performance optimization
- 🔲 Security audit

### MVP Target: Portfolio Website
Current focus is building a complete portfolio website with:
- Dynamic page management
- Image galleries
- Content blocks
- SEO optimization

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

*Last updated: 2025-10-19*
