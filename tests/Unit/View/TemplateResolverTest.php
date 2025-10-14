<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\View\TemplateResolver;

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
    $this->resolver = new TemplateResolver($this->moduleManager);
});

describe('TemplateResolver', function () {
    
    it('can resolve existing template file', function () {
        $path = $this->resolver->resolve('Infinri_Core::test.phtml');
        
        expect($path)->not->toBeNull();
        expect($path)->toContain('test.phtml');
        expect(file_exists($path))->toBeTrue();
    });
    
    it('returns null for non-existent template', function () {
        $path = $this->resolver->resolve('Infinri_Core::nonexistent.phtml');
        
        expect($path)->toBeNull();
    });
    
    it('returns null for invalid template path format', function () {
        $path = $this->resolver->resolve('invalid-path');
        
        expect($path)->toBeNull();
    });
    
    it('caches resolved template paths', function () {
        $path1 = $this->resolver->resolve('Infinri_Core::test.phtml');
        $path2 = $this->resolver->resolve('Infinri_Core::test.phtml');
        
        expect($path1)->toBe($path2);
    });
    
    it('can clear template cache', function () {
        $this->resolver->resolve('Infinri_Core::test.phtml');
        
        $this->resolver->clearCache();
        
        // Should still resolve after cache clear
        $path = $this->resolver->resolve('Infinri_Core::test.phtml');
        expect($path)->not->toBeNull();
    });
    
    it('resolves templates from different modules', function () {
        $corePath = $this->resolver->resolve('Infinri_Core::test.phtml');
        
        expect($corePath)->not->toBeNull();
        expect($corePath)->toContain('Infinri/Core');
    });
    
    it('tries multiple template locations', function () {
        // Should find template in view/frontend/templates/
        $path = $this->resolver->resolve('Infinri_Core::header/logo.phtml');
        
        expect($path)->not->toBeNull();
        expect($path)->toContain('header/logo.phtml');
    });
    
});
