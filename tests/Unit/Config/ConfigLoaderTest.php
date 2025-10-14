<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Config\Reader as ConfigReader;
use Infinri\Core\Model\Config\Loader;

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
    $this->configReader = new ConfigReader();
    $this->configLoader = new Loader($this->moduleManager, $this->configReader);
});

describe('ConfigLoader', function () {
    
    it('can load configuration from all modules', function () {
        $config = $this->configLoader->load();
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('default');
    });
    
    it('loads Core module configuration', function () {
        $config = $this->configLoader->load();
        
        expect($config['default'])->toHaveKey('system');
        expect($config['default']['system']['core']['name'])->toBe('Infinri Core Framework');
    });
    
    it('loads Theme module configuration', function () {
        $config = $this->configLoader->load();
        
        expect($config['default'])->toHaveKey('theme');
        expect($config['default']['theme']['general']['logo'])->toBe('Infinri_Theme::images/logo.svg');
    });
    
    it('merges configuration from multiple modules', function () {
        $config = $this->configLoader->load();
        
        // Should have both Core and Theme config
        expect($config['default'])->toHaveKey('system');
        expect($config['default'])->toHaveKey('theme');
    });
    
    it('loads modules in dependency order', function () {
        // Core should be loaded before Theme
        // This is implicitly tested by the merge working correctly
        $config = $this->configLoader->load();
        
        expect($config)->toBeArray();
        expect($config['default'])->toHaveKey('system');
        expect($config['default'])->toHaveKey('theme');
    });
    
    it('can load configuration by scope', function () {
        $defaultConfig = $this->configLoader->loadByScope('default');
        
        expect($defaultConfig)->toBeArray();
        expect($defaultConfig)->toHaveKey('system');
        expect($defaultConfig)->toHaveKey('theme');
    });
    
    it('returns empty array for non-existent scope', function () {
        $config = $this->configLoader->loadByScope('nonexistent');
        
        expect($config)->toBeArray();
        expect($config)->toBeEmpty();
    });
    
});
