<?php

declare(strict_types=1);

/**
 * Module Auto-Discovery
 *
 * Automatically discovers and executes registration.php files from all modules.
 */

$registrationFiles = glob(__DIR__ . '/../Infinri/*/registration.php');

if ($registrationFiles === false) {
    throw new RuntimeException('Failed to discover module registration files');
}

// Execute each registration file
foreach ($registrationFiles as $file) {
    require $file;
}
