<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Di\XmlReader;
use Infinri\Core\Model\Di\ContainerFactory;
use Infinri\Core\Model\ObjectManager;

// Shared container to avoid recreating for each test
$sharedContainer = null;

beforeEach(function () use (&$sharedContainer) {
    // Create container only once, reuse for all tests
    if ($sharedContainer === null) {
        // Reset singleton
        $reflection = new ReflectionClass(ComponentRegistrar::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        // Register modules
        require __DIR__ . '/../../../app/etc/registration_globlist.php';
        
        $registrar = ComponentRegistrar::getInstance();
        $moduleReader = new ModuleReader();
        $moduleList = new ModuleList($registrar, $moduleReader);
        $moduleManager = new ModuleManager($moduleList);
        $xmlReader = new XmlReader();
        $containerFactory = new ContainerFactory($moduleManager, $xmlReader);
        
        $sharedContainer = $containerFactory->create(false);
    }
    
    // Reset ObjectManager and set instance
    ObjectManager::reset();
    $this->objectManager = ObjectManager::setInstance($sharedContainer);
});

afterEach(function () {
    ObjectManager::reset();
});

describe('ObjectManager', function () {
    
    it('is a singleton', function () {
        $om1 = ObjectManager::getInstance();
        $om2 = ObjectManager::getInstance();
        
        expect($om1)->toBe($om2);
    });
    
    it('can get objects from container', function () {
        $moduleReader = $this->objectManager->get('Infinri\Core\Model\Module\ModuleReader');
        
        expect($moduleReader)->toBeInstanceOf('Infinri\Core\Model\Module\ModuleReader');
    });
    
    it('can create new instances', function () {
        $moduleReader = $this->objectManager->create('Infinri\Core\Model\Module\ModuleReader');
        
        expect($moduleReader)->toBeInstanceOf('Infinri\Core\Model\Module\ModuleReader');
    });
    
    it('resolves interfaces to implementations', function () {
        $config = $this->objectManager->get('Infinri\Core\Api\ConfigInterface');
        
        expect($config)->toBeInstanceOf('Infinri\Core\Model\Config\ScopeConfig');
    });
    
    it('can check if container has a class', function () {
        expect($this->objectManager->has('Infinri\Core\Model\Module\ModuleReader'))->toBeTrue();
        expect($this->objectManager->has('NonExistent\Class'))->toBeFalse();
    });
    
    it('provides access to underlying container', function () {
        $container = $this->objectManager->getContainer();
        
        expect($container)->toBeInstanceOf('Psr\Container\ContainerInterface');
    });
    
    it('throws exception when not configured', function () {
        ObjectManager::reset();
        
        ObjectManager::getInstance();
    })->throws(RuntimeException::class, 'ObjectManager not configured');
    
});
