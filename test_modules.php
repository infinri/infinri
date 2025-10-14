<?php
declare(strict_types=1);

/**
 * Test Module Registration System
 * 
 * Quick test script to verify ComponentRegistrar and module discovery works.
 */

require __DIR__ . '/app/autoload.php';

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;

echo "=== Infinri Module Registration Test ===\n\n";

// Test 1: Check ComponentRegistrar
echo "Test 1: ComponentRegistrar\n";
echo "----------------------------\n";

$registrar = ComponentRegistrar::getInstance();
$modules = $registrar->getPaths(ComponentRegistrar::MODULE);

echo "Registered modules:\n";
foreach ($modules as $name => $path) {
    echo "  - {$name}\n";
    echo "    Path: {$path}\n";
}
echo "\n";

// Test 2: Check ModuleReader
echo "Test 2: ModuleReader\n";
echo "----------------------------\n";

$moduleReader = new ModuleReader();

foreach ($modules as $name => $path) {
    $data = $moduleReader->read($path);
    
    if ($data) {
        echo "Module: {$name}\n";
        echo "  Name: {$data['name']}\n";
        echo "  Version: {$data['setup_version']}\n";
        echo "  Dependencies: " . (empty($data['sequence']) ? 'None' : implode(', ', $data['sequence'])) . "\n";
    } else {
        echo "Module: {$name}\n";
        echo "  ERROR: Could not read module.xml\n";
    }
    echo "\n";
}

// Test 3: Check ModuleList
echo "Test 3: ModuleList\n";
echo "----------------------------\n";

$moduleList = new ModuleList($registrar, $moduleReader);

echo "All modules:\n";
foreach ($moduleList->getAll() as $name => $data) {
    echo "  - {$name} (v{$data['setup_version']})\n";
}
echo "\n";

// Test 4: Check ModuleManager
echo "Test 4: ModuleManager\n";
echo "----------------------------\n";

$moduleManager = new ModuleManager($moduleList);

echo "Enabled modules:\n";
foreach ($moduleManager->getEnabledModuleNames() as $name) {
    echo "  - {$name}\n";
}
echo "\n";

echo "Modules in dependency order:\n";
foreach ($moduleManager->getModulesInOrder() as $name) {
    echo "  - {$name}\n";
}
echo "\n";

// Test 5: Check specific module
echo "Test 5: Check Infinri_Core\n";
echo "----------------------------\n";

if ($registrar->isRegistered(ComponentRegistrar::MODULE, 'Infinri_Core')) {
    echo "✓ Infinri_Core is registered\n";
    echo "  Path: " . $registrar->getPath(ComponentRegistrar::MODULE, 'Infinri_Core') . "\n";
} else {
    echo "✗ Infinri_Core is NOT registered\n";
}

if ($moduleManager->isEnabled('Infinri_Core')) {
    echo "✓ Infinri_Core is enabled\n";
} else {
    echo "✗ Infinri_Core is NOT enabled\n";
}
echo "\n";

// Test 6: Check Infinri_Theme
echo "Test 6: Check Infinri_Theme\n";
echo "----------------------------\n";

if ($registrar->isRegistered(ComponentRegistrar::MODULE, 'Infinri_Theme')) {
    echo "✓ Infinri_Theme is registered\n";
    echo "  Path: " . $registrar->getPath(ComponentRegistrar::MODULE, 'Infinri_Theme') . "\n";
} else {
    echo "✗ Infinri_Theme is NOT registered\n";
}

if ($moduleManager->isEnabled('Infinri_Theme')) {
    echo "✓ Infinri_Theme is enabled\n";
} else {
    echo "✗ Infinri_Theme is NOT enabled\n";
}

$themeData = $moduleList->getOne('Infinri_Theme');
if ($themeData) {
    echo "  Dependencies: " . implode(', ', $themeData['sequence']) . "\n";
}
echo "\n";

echo "=== All Tests Complete ===\n";
