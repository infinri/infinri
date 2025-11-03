<?php
declare(strict_types=1);

/**
 * Application Autoloader
 *
 * Loads Composer autoloader and executes module registration discovery.
 */

// Load Composer autoloader
$composerAutoloader = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($composerAutoloader)) {
    throw new RuntimeException(
        'Composer autoloader not found. Please run: composer install'
    );
}

require $composerAutoloader;

// Auto-discover and register all modules
require __DIR__ . '/etc/registration_globlist.php';
