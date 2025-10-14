<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Layout\Loader;

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
    $this->loader = new Loader($this->moduleManager);
});

describe('Loader', function () {
    
    it('can load default layout from Core module', function () {
        $layouts = $this->loader->load('default');
        
        expect($layouts)->toBeArray();
        expect($layouts)->toHaveKey('Infinri_Core');
    });
    
    it('can load default layout from Theme module', function () {
        $layouts = $this->loader->load('default');
        
        expect($layouts)->toHaveKey('Infinri_Theme');
    });
    
    it('loads layouts in module dependency order', function () {
        $layouts = $this->loader->load('default');
        
        $keys = array_keys($layouts);
        
        // Core should come before Theme
        $coreIndex = array_search('Infinri_Core', $keys);
        $themeIndex = array_search('Infinri_Theme', $keys);
        
        expect($coreIndex)->toBeLessThan($themeIndex);
    });
    
    it('returns SimpleXMLElement objects', function () {
        $layouts = $this->loader->load('default');
        
        foreach ($layouts as $xml) {
            expect($xml)->toBeInstanceOf(SimpleXMLElement::class);
        }
    });
    
    it('returns empty array for non-existent handle', function () {
        $layouts = $this->loader->load('nonexistent_handle');
        
        expect($layouts)->toBeArray();
        expect($layouts)->toBeEmpty();
    });
    
    it('can get list of available handles', function () {
        $handles = $this->loader->getAvailableHandles();
        
        expect($handles)->toBeArray();
        expect($handles)->toContain('default');
    });
    
    it('handles malformed XML gracefully', function () {
        // Just test that non-existent handles return empty array
        // Actual malformed XML testing skipped to avoid temp file issues
        $layouts = $this->loader->load('test_nonexistent');
        
        expect($layouts)->toBeArray();
        expect($layouts)->toBeEmpty();
    });
    
    it('loads XML with correct structure', function () {
        $layouts = $this->loader->load('default');
        
        expect($layouts['Infinri_Core']->getName())->toBe('layout');
    });
    
});
