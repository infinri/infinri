# Infinri Setup Guide

## Prerequisites Installation

### 1. Install PHP 8.4

**Option A: Using Chocolatey (Recommended)**
```powershell
# Install Chocolatey if not installed
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Install PHP 8.4
choco install php --version=8.4.0 -y

# Verify installation
php --version
```

**Option B: Manual Download**
1. Download PHP 8.4 from: https://windows.php.net/download/
2. Extract to `C:\php84`
3. Add `C:\php84` to system PATH
4. Restart PowerShell
5. Verify: `php --version`

### 2. Install Composer

```powershell
# Download Composer installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Install Composer globally
php composer-setup.php --install-dir=C:\php84 --filename=composer

# Remove installer
php -r "unlink('composer-setup.php');"

# Verify
composer --version
```

### 3. Install Dependencies

```bash
# Navigate to project
cd C:\www\infinri

# Install PHP dependencies
composer install

# Install Node.js dependencies (for asset compilation)
npm install
```

---

## Running Tests

### Module Registration Test

```bash
# Run the module registration test
php test_modules.php
```

**Expected Output:**
```
=== Infinri Module Registration Test ===

Test 1: ComponentRegistrar
----------------------------
Registered modules:
  - Infinri_Core
    Path: C:\www\infinri\app\Infinri\Core
  - Infinri_Theme
    Path: C:\www\infinri\app\Infinri\Theme

Test 2: ModuleReader
----------------------------
Module: Infinri_Core
  Name: Infinri_Core
  Version: 0.1.0
  Dependencies: None

Module: Infinri_Theme
  Name: Infinri_Theme
  Version: 0.1.0
  Dependencies: Infinri_Core

...
✓ All tests passing
```

### PHPUnit Tests (Once Available)

```bash
# Run all tests
composer test

# Run specific test suite
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Integration/

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### PHPStan Static Analysis

```bash
# Run static analysis
composer phpstan

# Or directly
vendor/bin/phpstan analyse
```

---

## Quick Start (After Setup)

```bash
# 1. Verify modules are registered
php test_modules.php

# 2. Enable development mode
# Edit app/etc/env.php and set: 'dev_mode' => 1

# 3. Deploy assets
php bin/console asset:deploy

# 4. Start development server
php -S localhost:8000 -t pub/

# 5. Open browser
# Navigate to: http://localhost:8000
```

---

## Troubleshooting

### "php is not recognized"
- PHP is not in your PATH
- Run: `$env:Path += ";C:\php84"` (temporary)
- Or add to system PATH permanently

### "composer is not recognized"
- Composer not installed or not in PATH
- Install Composer (see step 2 above)

### "Class not found" errors
- Run: `composer dump-autoload`
- Ensure vendor/autoload.php exists

### "Module not found"
- Check app/etc/config.php has module enabled
- Verify module registration.php exists
- Run: `php test_modules.php` to debug

---

## Development Environment Setup

### Recommended Extensions

**PHP Extensions (enabled in php.ini):**
```ini
extension=pdo_pgsql
extension=pgsql
extension=mbstring
extension=openssl
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=zip
```

**PostgreSQL:**
- Download: https://www.postgresql.org/download/windows/
- Install PostgreSQL 14+
- Create database: `createdb infinri`

**IDE Setup (VSCode/PHPStorm):**
- Install PHP Intelephense or PHP Language Server
- Install ESLint for JavaScript
- Install LESS syntax highlighting

---

## Next Steps

After completing setup:

1. ✅ Run `php test_modules.php` - Verify module registration
2. ✅ Run `composer install` - Install dependencies
3. ✅ Configure `app/etc/env.php` - Database credentials
4. ✅ Run `php bin/console cache:clear` - Test CLI
5. ✅ Continue Core Framework implementation

---

## Current Implementation Status

✅ **Phase 1: Component Registration** - COMPLETE
- ComponentRegistrar system
- Module discovery and loading
- Module dependency resolution
- Bootstrap files created

⏳ **Phase 2: Configuration System** - Next
- Config XML reader
- ScopeConfig implementation
- Config caching

⏳ **Phase 3: DI Container** - Pending
⏳ **Phase 4: Layout System** - Pending
⏳ **Phase 5+: See requirements.txt** - Pending
