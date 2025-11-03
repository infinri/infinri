# Infinri Framework - Development Tools
# Usage: make [target]

.PHONY: help install test analyze fix check ci clean

# Default target
help: ## Show this help message
	@echo "Infinri Framework - Development Tools"
	@echo ""
	@echo "Available targets:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install all dependencies
	composer install
	composer install --dev

# Static Analysis
analyze: ## Run PHPStan static analysis
	@echo "🔍 Running PHPStan static analysis..."
	vendor/bin/phpstan analyse --memory-limit=1G

analyze-baseline: ## Generate PHPStan baseline for existing issues
	@echo "📝 Generating PHPStan baseline..."
	vendor/bin/phpstan analyse --generate-baseline

analyze-fix: ## Fix PHPStan issues systematically
	@echo "🔧 Fixing PHPStan issues..."
	chmod +x fix-phpstan.sh
	./fix-phpstan.sh

analyze-fix-remaining: ## Fix remaining PHPStan issues after baseline
	@echo "🔧 Fixing remaining PHPStan issues..."
	php fix-remaining-phpstan.php

analyze-strict: ## Run PHPStan without baseline (show all issues)
	@echo "🔍 Running strict PHPStan analysis..."
	vendor/bin/phpstan analyse --no-baseline --memory-limit=1G

# Code Style
cs-check: ## Check coding standards with PHP_CodeSniffer
	@echo "📏 Checking coding standards..."
	vendor/bin/phpcs

cs-fix: ## Fix coding standards with PHP_CodeSniffer
	@echo "🔧 Fixing coding standards..."
	vendor/bin/phpcbf

# PHP CS Fixer
fix: ## Fix code style with PHP CS Fixer
	@echo "✨ Fixing code style with PHP CS Fixer..."
	vendor/bin/php-cs-fixer fix --verbose

fix-dry: ## Preview PHP CS Fixer changes without applying
	@echo "👀 Previewing PHP CS Fixer changes..."
	vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# Combined checks
check: cs-check analyze ## Run all code quality checks
	@echo "✅ All checks completed!"

# CI Pipeline
ci: install check test ## Run full CI pipeline
	@echo "🚀 CI pipeline completed!"

# Testing
test: ## Run PHPUnit tests
	@echo "🧪 Running tests..."
	vendor/bin/phpunit

test-coverage: ## Run tests with coverage report
	@echo "📊 Running tests with coverage..."
	vendor/bin/phpunit --coverage-html var/coverage

# Cache management
cache-clear: ## Clear all caches
	@echo "🧹 Clearing caches..."
	rm -rf var/cache/*
	php bin/console cache:clear

# Cleanup
clean: ## Clean generated files and caches
	@echo "🧹 Cleaning up..."
	rm -rf var/cache/*
	rm -rf var/log/*
	rm -rf .phpunit.result.cache
	rm -rf .php-cs-fixer.cache

# Development helpers
dev-setup: install ## Setup development environment
	@echo "🛠️ Setting up development environment..."
	mkdir -p var/cache var/log
	chmod 755 var/cache var/log
	@echo "✅ Development environment ready!"

# Quality gates
quality-gate: ## Run quality gate checks (strict)
	@echo "🚪 Running quality gate checks..."
	vendor/bin/phpstan analyse --no-progress --error-format=table
	vendor/bin/phpcs --report=summary
	vendor/bin/php-cs-fixer fix --dry-run --diff
	@echo "✅ Quality gate passed!"

# Documentation
docs: ## Generate documentation
	@echo "📚 Generating documentation..."
	@echo "Documentation generation not yet implemented"

# Database
db-setup: ## Setup database schema
	@echo "🗄️ Setting up database..."
	php bin/console setup:install --skip-admin

db-migrate: ## Run database migrations
	@echo "🔄 Running database migrations..."
	php bin/console schema:migrate

# Performance
benchmark: ## Run performance benchmarks
	@echo "⚡ Running benchmarks..."
	@echo "Benchmarking not yet implemented"
