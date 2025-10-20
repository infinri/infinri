<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Layout\Loader;
use Infinri\Core\Model\Layout\Merger;

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
    $this->merger = new Merger();
});

describe('Merger', function () {
    
    it('can merge multiple layout XMLs', function () {
        $layouts = $this->loader->load('default');
        
        $merged = $this->merger->merge($layouts);
        
        expect($merged)->toBeInstanceOf(SimpleXMLElement::class);
        expect($merged->getName())->toBe('layout');
    });
    
    it('merges layouts from all modules', function () {
        $layouts = $this->loader->load('default');
        
        $merged = $this->merger->merge($layouts);
        
        // Should have elements from both Core and Theme
        expect($merged->count())->toBeGreaterThan(0);
    });
    
    it('preserves element structure during merge', function () {
        $layouts = $this->loader->load('base_default');
        
        $merged = $this->merger->merge($layouts);
        
        // Check for container from base_default layout
        $containers = $merged->xpath('//container[@name="root"]');
        expect($containers)->not->toBeEmpty();
    });
    
    it('handles empty layouts array', function () {
        $merged = $this->merger->merge([]);
        
        expect($merged)->toBeInstanceOf(SimpleXMLElement::class);
        expect($merged->getName())->toBe('layout');
        expect($merged->count())->toBe(0);
    });
    
    it('merges child elements correctly', function () {
        $xml1 = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="test"/></layout>');
        $xml2 = new SimpleXMLElement('<?xml version="1.0"?><layout><block name="test2"/></layout>');
        
        $merged = $this->merger->merge(['module1' => $xml1, 'module2' => $xml2]);
        
        expect($merged->count())->toBe(2);
    });
    
    it('preserves attributes during merge', function () {
        $xml1 = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="test" htmlTag="div" htmlClass="test-class"/></layout>');
        
        $merged = $this->merger->merge(['module1' => $xml1]);
        
        $container = $merged->container[0];
        expect((string) $container['name'])->toBe('test');
        expect((string) $container['htmlTag'])->toBe('div');
        expect((string) $container['htmlClass'])->toBe('test-class');
    });
    
});
