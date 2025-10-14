@echo off
REM Test Module Registration System
REM Runs test_modules.php

echo ================================
echo Infinri Module Registration Test
echo ================================
echo.

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo.
    echo Please install PHP 8.4+ and add it to your PATH.
    echo See SETUP.md for installation instructions.
    echo.
    pause
    exit /b 1
)

REM Show PHP version
echo PHP Version:
php --version
echo.

REM Run the test
echo Running module registration tests...
echo.
php test_modules.php

echo.
echo ================================
echo Test Complete
echo ================================
pause
