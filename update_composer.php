<?php
/**
 * Update composer.json with proper autoloading and Pest test command
 */

$composerFile = __DIR__ . '/composer.json';
$composer = json_decode(file_get_contents($composerFile), true);

// Add Core and Theme to autoload
$composer['autoload']['psr-4']['Infinri\\Core\\'] = 'app/Infinri/Core';
$composer['autoload']['psr-4']['Infinri\\Theme\\'] = 'app/Infinri/Theme';

// Update test script to use Pest
$composer['scripts']['test'] = 'vendor/bin/pest';

// Add new test scripts
$composer['scripts']['test:unit'] = 'vendor/bin/pest tests/Unit';
$composer['scripts']['test:integration'] = 'vendor/bin/pest tests/Integration';
$composer['scripts']['test:coverage'] = 'vendor/bin/pest --coverage --min=80';

// Update autoload-dev
$composer['autoload-dev']['psr-4'] = ['Tests\\' => 'tests/'];

// Write back with pretty print
file_put_contents(
    $composerFile, 
    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "✓ composer.json updated successfully!\n";
echo "✓ Added Infinri\\Core\\ and Infinri\\Theme\\ to autoload\n";
echo "✓ Changed test command to use Pest\n";
echo "✓ Added test:unit, test:integration, test:coverage scripts\n";
echo "\nNext steps:\n";
echo "1. Run: composer dump-autoload\n";
echo "2. Run: composer test (or vendor/bin/pest)\n";
