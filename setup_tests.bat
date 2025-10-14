@echo off
REM Setup Pest Testing Framework

echo ================================
echo Infinri Test Setup
echo ================================
echo.

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP 8.4+ first. See SETUP.md
    echo.
    pause
    exit /b 1
)

echo Step 1: Updating composer.json...
php update_composer.php
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Failed to update composer.json
    pause
    exit /b 1
)
echo.

echo Step 2: Checking for Composer...
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Composer is not installed or not in PATH
    echo You'll need to run these manually:
    echo   1. Install Composer from: https://getcomposer.org/
    echo   2. Run: composer install
    echo   3. Run: composer dump-autoload
    echo   4. Run: composer test
    echo.
    pause
    exit /b 1
)

echo Step 3: Installing dependencies...
composer install
echo.

echo Step 4: Regenerating autoload files...
composer dump-autoload
echo.

echo ================================
echo Test Setup Complete!
echo ================================
echo.
echo You can now run tests:
echo   composer test           - Run all tests
echo   composer test:unit      - Run unit tests only
echo   composer test:coverage  - Run with coverage report
echo   vendor\bin\pest         - Direct Pest command
echo.
pause
