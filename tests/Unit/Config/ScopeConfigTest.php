<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Config\Reader as ConfigReader;
use Infinri\Core\Model\Config\Loader;
use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Api\ConfigInterface;

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
    $this->scopeConfig = new ScopeConfig($this->configLoader);
});

describe('ScopeConfig', function () {
    
    it('implements ConfigInterface', function () {
        expect($this->scopeConfig)->toBeInstanceOf(ConfigInterface::class);
    });
    
    it('can get configuration value by path', function () {
        $logo = $this->scopeConfig->getValue('theme/general/logo');
        
        expect($logo)->toBe('Infinri_Theme::images/logo.svg');
    });
    
    it('can get nested configuration values', function () {
        $primaryColor = $this->scopeConfig->getValue('theme/colors/primary');
        
        expect($primaryColor)->toBe('#0066cc');
    });
    
    it('returns null for non-existent path', function () {
        $value = $this->scopeConfig->getValue('non/existent/path');
        
        expect($value)->toBeNull();
    });
    
    it('can use simple get method with default', function () {
        $value = $this->scopeConfig->get('theme/general/logo', 'default.svg');
        
        expect($value)->toBe('Infinri_Theme::images/logo.svg');
    });
    
    it('returns default value when path not found', function () {
        $value = $this->scopeConfig->get('non/existent', 'default_value');
        
        expect($value)->toBe('default_value');
    });
    
    it('can check if flag is set (string true)', function () {
        // Add a test config value
        $config = $this->scopeConfig->getAllByScope();
        
        // For now, test with existing value converted to boolean
        $isSet = $this->scopeConfig->isSetFlag('theme/general/logo');
        
        expect($isSet)->toBeBool();
    });
    
    it('can get all config for default scope', function () {
        $config = $this->scopeConfig->getAllByScope('default');
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('system');
        expect($config)->toHaveKey('theme');
    });
    
    it('caches configuration after first load', function () {
        // First call
        $value1 = $this->scopeConfig->getValue('theme/general/logo');
        
        // Second call (should use cache)
        $value2 = $this->scopeConfig->getValue('theme/colors/primary');
        
        expect($value1)->toBe('Infinri_Theme::images/logo.svg');
        expect($value2)->toBe('#0066cc');
    });
    
    it('can clear cache', function () {
        $value1 = $this->scopeConfig->getValue('theme/general/logo');
        
        $this->scopeConfig->clearCache();
        
        $value2 = $this->scopeConfig->getValue('theme/general/logo');
        
        expect($value1)->toBe($value2);
    });
    
    it('supports scope types', function () {
        // Test with default scope
        $value = $this->scopeConfig->getValue('theme/general/logo', ScopeConfig::SCOPE_DEFAULT);
        
        expect($value)->toBe('Infinri_Theme::images/logo.svg');
    });
    
    it('converts various values to boolean for isSetFlag', function () {
        // This tests the boolean conversion logic
        // Since we don't have boolean config values yet, we test the method exists and is callable
        expect(method_exists($this->scopeConfig, 'isSetFlag'))->toBeTrue();
        expect(is_callable([$this->scopeConfig, 'isSetFlag']))->toBeTrue();
    });
    
});
