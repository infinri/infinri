<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Api\ComponentRegistrarInterface;

beforeEach(function () {
    // Reset singleton for each test
    $reflection = new ReflectionClass(ComponentRegistrar::class);
    $instance = $reflection->getProperty('instance');
    $instance->setAccessible(true);
    $instance->setValue(null, null);
});

describe('ComponentRegistrar', function () {
    
    it('is a singleton', function () {
        $instance1 = ComponentRegistrar::getInstance();
        $instance2 = ComponentRegistrar::getInstance();
        
        expect($instance1)->toBe($instance2);
    });
    
    it('implements ComponentRegistrarInterface', function () {
        $instance = ComponentRegistrar::getInstance();
        
        expect($instance)->toBeInstanceOf(ComponentRegistrarInterface::class);
    });
    
    it('can register a module', function () {
        $modulePath = __DIR__ . '/../../app/Infinri/Core';
        
        ComponentRegistrar::register(
            ComponentRegistrar::MODULE,
            'Test_Module',
            $modulePath
        );
        
        $registrar = ComponentRegistrar::getInstance();
        
        expect($registrar->isRegistered(ComponentRegistrar::MODULE, 'Test_Module'))->toBeTrue();
        expect($registrar->getPath(ComponentRegistrar::MODULE, 'Test_Module'))->toBe($modulePath);
    });
    
    it('can register multiple component types', function () {
        $modulePath = __DIR__ . '/../../app/Infinri/Core';
        $themePath = __DIR__ . '/../../app/Infinri/Theme';
        
        ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Test_Module', $modulePath);
        ComponentRegistrar::register(ComponentRegistrar::THEME, 'Test_Theme', $themePath);
        
        $registrar = ComponentRegistrar::getInstance();
        
        expect($registrar->isRegistered(ComponentRegistrar::MODULE, 'Test_Module'))->toBeTrue();
        expect($registrar->isRegistered(ComponentRegistrar::THEME, 'Test_Theme'))->toBeTrue();
    });
    
    it('returns all paths for a specific type', function () {
        $corePath = __DIR__ . '/../../app/Infinri/Core';
        $themePath = __DIR__ . '/../../app/Infinri/Theme';
        
        ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Infinri_Core', $corePath);
        ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Infinri_Theme', $themePath);
        
        $registrar = ComponentRegistrar::getInstance();
        $modules = $registrar->getPaths(ComponentRegistrar::MODULE);
        
        expect($modules)->toBeArray();
        expect($modules)->toHaveKey('Infinri_Core');
        expect($modules)->toHaveKey('Infinri_Theme');
        expect(count($modules))->toBeGreaterThanOrEqual(2);
    });
    
    it('throws exception for invalid component type', function () {
        $modulePath = __DIR__ . '/../../app/Infinri/Core';
        
        ComponentRegistrar::register('invalid_type', 'Test_Module', $modulePath);
    })->throws(InvalidArgumentException::class, 'Invalid component type');
    
    it('throws exception for non-existent path', function () {
        ComponentRegistrar::register(
            ComponentRegistrar::MODULE,
            'Test_Module',
            '/non/existent/path'
        );
    })->throws(InvalidArgumentException::class, 'Component path does not exist');
    
    it('normalizes paths by removing trailing slashes', function () {
        $modulePath = __DIR__ . '/../../app/Infinri/Core';
        
        ComponentRegistrar::register(
            ComponentRegistrar::MODULE,
            'Test_Module',
            $modulePath . '/'
        );
        
        $registrar = ComponentRegistrar::getInstance();
        $path = $registrar->getPath(ComponentRegistrar::MODULE, 'Test_Module');
        
        expect($path)->not->toEndWith('/');
        expect($path)->not->toEndWith('\\');
    });
    
    it('returns null for non-existent component', function () {
        $registrar = ComponentRegistrar::getInstance();
        
        expect($registrar->getPath(ComponentRegistrar::MODULE, 'NonExistent'))->toBeNull();
        expect($registrar->isRegistered(ComponentRegistrar::MODULE, 'NonExistent'))->toBeFalse();
    });
    
    it('returns empty array for component type with no registrations', function () {
        $registrar = ComponentRegistrar::getInstance();
        
        expect($registrar->getPaths(ComponentRegistrar::LANGUAGE))->toBeArray();
        expect($registrar->getPaths(ComponentRegistrar::LANGUAGE))->toBeEmpty();
    });
    
    it('cannot be cloned', function () {
        $registrar = ComponentRegistrar::getInstance();
        $clone = clone $registrar;
    })->throws(Error::class);
    
    it('cannot be unserialized', function () {
        $registrar = ComponentRegistrar::getInstance();
        $serialized = serialize($registrar);
        unserialize($serialized);
    })->throws(Exception::class, 'Cannot unserialize singleton');
    
});
