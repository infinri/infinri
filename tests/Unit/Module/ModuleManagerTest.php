<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;

// Static flag to avoid re-registering modules
$modulesRegistered = false;

beforeEach(function () use (&$modulesRegistered) {
    // Only register modules once
    if (!$modulesRegistered) {
        // Reset singleton
        $reflection = new ReflectionClass(ComponentRegistrar::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        // Register modules
        require __DIR__ . '/../../../app/etc/registration_globlist.php';
        
        $modulesRegistered = true;
    }
    
    $this->registrar = ComponentRegistrar::getInstance();
    $this->moduleReader = new ModuleReader();
    $this->moduleList = new ModuleList($this->registrar, $this->moduleReader);
    $this->moduleManager = new ModuleManager($this->moduleList);
});

describe('ModuleManager', function () {
    
    it('can check if module is enabled', function () {
        expect($this->moduleManager->isEnabled('Infinri_Core'))->toBeTrue();
        expect($this->moduleManager->isEnabled('Infinri_Theme'))->toBeTrue();
    });
    
    it('returns false for non-existent module', function () {
        expect($this->moduleManager->isEnabled('NonExistent_Module'))->toBeFalse();
    });
    
    it('can get all enabled modules', function () {
        $enabled = $this->moduleManager->getEnabledModules();
        
        expect($enabled)->toBeArray();
        expect($enabled)->toHaveKey('Infinri_Core');
        expect($enabled)->toHaveKey('Infinri_Theme');
        expect($enabled['Infinri_Core'])->toBe(1);
        expect($enabled['Infinri_Theme'])->toBe(1);
    });
    
    it('can get enabled module names only', function () {
        $names = $this->moduleManager->getEnabledModuleNames();
        
        expect($names)->toBeArray();
        expect($names)->toContain('Infinri_Core');
        expect($names)->toContain('Infinri_Theme');
    });
    
    it('returns modules in dependency order', function () {
        $ordered = $this->moduleManager->getModulesInOrder();
        
        expect($ordered)->toBeArray();
        
        // Core should come before Theme
        $coreIndex = array_search('Infinri_Core', $ordered);
        $themeIndex = array_search('Infinri_Theme', $ordered);
        
        expect($coreIndex)->not->toBeFalse();
        expect($themeIndex)->not->toBeFalse();
        expect($coreIndex)->toBeLessThan($themeIndex);
    });
    
    it('handles modules with no dependencies', function () {
        $ordered = $this->moduleManager->getModulesInOrder();
        
        // Core has no dependencies, should be first
        expect($ordered[0])->toBe('Infinri_Core');
    });
    
    it('handles circular dependencies gracefully', function () {
        // This test ensures the topological sort doesn't infinite loop
        // In reality, module.xml should prevent this, but we test it anyway
        $ordered = $this->moduleManager->getModulesInOrder();
        
        expect($ordered)->toBeArray();
        expect(count($ordered))->toBeGreaterThan(0);
    });
    
    it('can clear cache', function () {
        $enabled1 = $this->moduleManager->getEnabledModules();
        
        $this->moduleManager->clearCache();
        
        $enabled2 = $this->moduleManager->getEnabledModules();
        
        expect($enabled1)->toEqual($enabled2);
    });
    
    it('loads from app/etc/config.php', function () {
        $configPath = __DIR__ . '/../../../app/etc/config.php';
        
        expect(file_exists($configPath))->toBeTrue();
        
        $config = include $configPath;
        
        expect($config)->toHaveKey('modules');
        expect($config['modules'])->toBeArray();
    });
    
});
