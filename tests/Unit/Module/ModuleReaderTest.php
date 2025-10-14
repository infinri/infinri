<?php

declare(strict_types=1);

use Infinri\Core\Model\Module\ModuleReader;

describe('ModuleReader', function () {
    
    beforeEach(function () {
        $this->moduleReader = new ModuleReader();
    });
    
    it('can read Infinri_Core module.xml', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $data = $this->moduleReader->read($corePath);
        
        expect($data)->toBeArray();
        expect($data['name'])->toBe('Infinri_Core');
        expect($data['setup_version'])->toBe('0.1.0');
        expect($data['sequence'])->toBeArray();
        expect($data['sequence'])->toBeEmpty();
    });
    
    it('can read Infinri_Theme module.xml', function () {
        $themePath = __DIR__ . '/../../../app/Infinri/Theme';
        
        $data = $this->moduleReader->read($themePath);
        
        expect($data)->toBeArray();
        expect($data['name'])->toBe('Infinri_Theme');
        expect($data['setup_version'])->toBe('0.1.0');
        expect($data['sequence'])->toBeArray();
        expect($data['sequence'])->toContain('Infinri_Core');
    });
    
    it('returns null for missing module.xml', function () {
        $invalidPath = __DIR__;
        
        $data = $this->moduleReader->read($invalidPath);
        
        expect($data)->toBeNull();
    });
    
    it('validates module.xml exists', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $isValid = $this->moduleReader->validate($corePath);
        
        expect($isValid)->toBeTrue();
    });
    
    it('validates fails for invalid path', function () {
        $invalidPath = __DIR__;
        
        $isValid = $this->moduleReader->validate($invalidPath);
        
        expect($isValid)->toBeFalse();
    });
    
    it('handles malformed XML gracefully', function () {
        // Create a temporary directory with invalid XML
        $tempDir = sys_get_temp_dir() . '/test_module_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/etc');
        
        file_put_contents($tempDir . '/etc/module.xml', '<?xml version="1.0"?><invalid>');
        
        $data = $this->moduleReader->read($tempDir);
        
        // Cleanup
        unlink($tempDir . '/etc/module.xml');
        rmdir($tempDir . '/etc');
        rmdir($tempDir);
        
        expect($data)->toBeNull();
    });
    
});
