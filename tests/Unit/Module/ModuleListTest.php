<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;

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
});

describe('ModuleList', function () {
    
    it('can get all registered modules', function () {
        $modules = $this->moduleList->getAll();
        
        expect($modules)->toBeArray();
        expect($modules)->toHaveKey('Infinri_Core');
        expect($modules)->toHaveKey('Infinri_Theme');
    });
    
    it('includes module paths in module data', function () {
        $modules = $this->moduleList->getAll();
        
        expect($modules['Infinri_Core'])->toHaveKey('path');
        expect($modules['Infinri_Core']['path'])->toContain('app/Infinri/Core');
    });
    
    it('can get all module names', function () {
        $names = $this->moduleList->getNames();
        
        expect($names)->toBeArray();
        expect($names)->toContain('Infinri_Core');
        expect($names)->toContain('Infinri_Theme');
    });
    
    it('can get a single module', function () {
        $core = $this->moduleList->getOne('Infinri_Core');
        
        expect($core)->toBeArray();
        expect($core['name'])->toBe('Infinri_Core');
        expect($core['setup_version'])->toBe('0.1.0');
    });
    
    it('returns null for non-existent module', function () {
        $module = $this->moduleList->getOne('NonExistent_Module');
        
        expect($module)->toBeNull();
    });
    
    it('can check if module exists', function () {
        expect($this->moduleList->has('Infinri_Core'))->toBeTrue();
        expect($this->moduleList->has('Infinri_Theme'))->toBeTrue();
        expect($this->moduleList->has('NonExistent_Module'))->toBeFalse();
    });
    
    it('caches module data', function () {
        // First call
        $modules1 = $this->moduleList->getAll();
        
        // Second call (should use cache)
        $modules2 = $this->moduleList->getAll();
        
        expect($modules1)->toBe($modules2);
    });
    
    it('can clear cache', function () {
        $modules1 = $this->moduleList->getAll();
        
        $this->moduleList->clearCache();
        
        $modules2 = $this->moduleList->getAll();
        
        // Should be equal but not the same instance
        expect($modules1)->toEqual($modules2);
    });
    
});
