<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Di\XmlReader;
use Infinri\Core\Model\Di\ContainerFactory;
use Psr\Container\ContainerInterface;

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
    $this->xmlReader = new XmlReader();
    $this->containerFactory = new ContainerFactory($this->moduleManager, $this->xmlReader);
});

describe('ContainerFactory', function () {
    
    it('can create a DI container', function () {
        $container = $this->containerFactory->create(false);
        
        expect($container)->toBeInstanceOf(ContainerInterface::class);
    });
    
    it('container has PSR-11 interface', function () {
        $container = $this->containerFactory->create(false);
        
        expect(method_exists($container, 'get'))->toBeTrue();
        expect(method_exists($container, 'has'))->toBeTrue();
    });
    
    it('loads definitions from all modules', function () {
        $container = $this->containerFactory->create(false);
        
        // Should have loaded preferences from Core module
        expect($container->has('Infinri\Core\Api\ConfigInterface'))->toBeTrue();
    });
    
    it('resolves interface to implementation via preferences', function () {
        $container = $this->containerFactory->create(false);
        
        $config = $container->get('Infinri\Core\Api\ConfigInterface');
        
        expect($config)->toBeInstanceOf('Infinri\Core\Model\Config\ScopeConfig');
    });
    
    it('can instantiate classes with dependencies', function () {
        $container = $this->containerFactory->create(false);
        
        $scopeConfig = $container->get('Infinri\Core\Model\Config\ScopeConfig');
        
        expect($scopeConfig)->toBeInstanceOf('Infinri\Core\Model\Config\ScopeConfig');
    });
    
    it('supports autowiring', function () {
        $container = $this->containerFactory->create(false);
        
        // ModuleReader has no constructor dependencies
        $moduleReader = $container->get('Infinri\Core\Model\Module\ModuleReader');
        
        expect($moduleReader)->toBeInstanceOf('Infinri\Core\Model\Module\ModuleReader');
    });
    
});
