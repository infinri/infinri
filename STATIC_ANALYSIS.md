# Static Analysis & Code Quality Tools

This document describes the static analysis and code quality tools configured for the Infinri Framework.

## 🛠️ Tools Overview

### 1. PHPStan - Static Analysis
- **Level**: 8 (strict analysis)
- **Purpose**: Finds bugs without running code
- **Configuration**: `phpstan.neon`

### 2. PHP_CodeSniffer - Coding Standards
- **Standard**: PSR-12 with custom rules
- **Purpose**: Enforces coding standards
- **Configuration**: `phpcs.xml`

### 3. PHP CS Fixer - Code Formatting
- **Standard**: PSR-12 + Symfony + Modern PHP
- **Purpose**: Automatically fixes code style
- **Configuration**: `.php-cs-fixer.php`

## 📦 Installation

### Install Dependencies
```bash
# Install all static analysis tools
composer require --dev phpstan/phpstan
composer require --dev phpstan/extension-installer
composer require --dev phpstan/phpstan-symfony
composer require --dev phpstan/phpstan-strict-rules
composer require --dev squizlabs/php_codesniffer
composer require --dev friendsofphp/php-cs-fixer
```

### Setup Git Hooks (Optional)
```bash
# Install pre-commit hooks
chmod +x install-hooks.sh
./install-hooks.sh
```

## 🚀 Usage

### Using Make Commands (Recommended)
```bash
# Run all checks
make check

# Individual tools
make analyze          # PHPStan analysis
make analyze-fix      # Fix PHPStan issues systematically
make analyze-strict   # Run PHPStan without baseline (show all issues)
make analyze-baseline # Generate PHPStan baseline
make cs-check         # Check coding standards
make cs-fix           # Fix coding standards
make fix              # Fix code style with PHP CS Fixer
make fix-dry          # Preview fixes without applying

# Development workflow
make dev-setup        # Setup development environment
make quality-gate     # Strict quality checks for CI
```

### Direct Tool Usage

#### PHPStan
```bash
# Basic analysis
vendor/bin/phpstan analyse

# With memory limit
vendor/bin/phpstan analyse --memory-limit=1G

# Generate baseline for existing issues
vendor/bin/phpstan analyse --generate-baseline
```

#### PHP_CodeSniffer
```bash
# Check coding standards
vendor/bin/phpcs

# Fix automatically fixable issues
vendor/bin/phpcbf

# Check specific files
vendor/bin/phpcs app/Infinri/Core/Model/
```

#### PHP CS Fixer
```bash
# Fix code style
vendor/bin/php-cs-fixer fix

# Preview changes
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix specific directory
vendor/bin/php-cs-fixer fix app/Infinri/Core/
```

## ⚙️ Configuration Details

### PHPStan Configuration (`phpstan.neon`)
- **Analysis Level**: 8 (strictest)
- **Paths**: `app/Infinri/`, `bin/console`
- **Excludes**: Tests, var, pub directories
- **Extensions**: Symfony integration
- **Memory**: 1GB limit with caching

### PHPCS Configuration (`phpcs.xml`)
- **Standard**: PSR-12 with additional rules
- **Line Length**: 120 characters (150 absolute max)
- **Parallel Processing**: 8 processes
- **Caching**: Enabled for faster runs

### PHP CS Fixer Configuration (`.php-cs-fixer.php`)
- **Standards**: PSR-12 + Symfony + Modern PHP
- **Risky Rules**: Enabled for better code quality
- **Features**: 
  - Strict types enforcement
  - Modern PHP syntax
  - Consistent formatting

## 🔍 Quality Gates

### Pre-commit Checks
The pre-commit hook runs:
1. PHP syntax validation
2. PHPStan analysis (level 8)
3. Code style checks
4. Debugging statement detection
5. TODO/FIXME comment warnings

### CI Pipeline Checks
GitHub Actions runs:
1. Static analysis (PHPStan)
2. Coding standards (PHPCS)
3. Code style (PHP CS Fixer)
4. Unit tests with coverage
5. Security audit

## 📊 Metrics & Reports

### Coverage Reports
```bash
# Generate HTML coverage report
make test-coverage
# Report available at: var/coverage/index.html
```

### Analysis Reports
```bash
# PHPStan with detailed output
vendor/bin/phpstan analyse --error-format=table

# PHPCS summary report
vendor/bin/phpcs --report=summary

# PHP CS Fixer diff report
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## 🛡️ Bypassing Checks

### Temporary Bypass
```bash
# Skip pre-commit hooks
git commit --no-verify

# Skip specific PHPStan errors (use sparingly)
# Add to phpstan.neon ignoreErrors section
```

### Permanent Exclusions
```bash
# Exclude files from analysis
# Add to phpstan.neon excludePaths section

# Exclude from coding standards
# Add to phpcs.xml exclude-pattern
```

## 🔧 Troubleshooting

### Common Issues

#### Memory Limit Errors
```bash
# Increase PHPStan memory limit
vendor/bin/phpstan analyse --memory-limit=2G

# Or set in phpstan.neon
echo "memory_limit: 2G" >> phpstan.neon
```

#### Cache Issues
```bash
# Clear PHPStan cache
rm -rf var/cache/phpstan

# Clear PHP CS Fixer cache
rm -rf var/cache/.php-cs-fixer.cache

# Clear all caches
make clean
```

#### Performance Issues
```bash
# Use parallel processing
vendor/bin/phpstan analyse --parallel

# Reduce analysis scope
vendor/bin/phpstan analyse app/Infinri/Core/
```

## 📈 Best Practices

### Development Workflow
1. **Write code** following PSR-12 standards
2. **Run checks** frequently during development
3. **Fix issues** before committing
4. **Use IDE integration** for real-time feedback

### Code Quality Rules
1. **No debugging statements** in committed code
2. **Strict type declarations** required
3. **PHPDoc comments** for public methods
4. **Meaningful variable names**
5. **Single responsibility** principle

### Performance Optimization
1. **Use caching** for faster subsequent runs
2. **Parallel processing** when available
3. **Incremental analysis** for large codebases
4. **Baseline files** for legacy code

## 🎯 IDE Integration

### PhpStorm
1. Install PHPStan plugin
2. Configure PHP CS Fixer as external tool
3. Enable real-time inspection

### VS Code
1. Install PHP Intelephense extension
2. Configure PHPStan integration
3. Add PHP CS Fixer extension

## 📚 Resources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP_CodeSniffer Documentation](https://github.com/squizlabs/PHP_CodeSniffer/wiki)
- [PHP CS Fixer Documentation](https://cs.symfony.com/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
