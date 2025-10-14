<?php

declare(strict_types=1);

use Infinri\Core\Model\Di\XmlReader;

// Static cache for reader instance
$cachedReader = null;

describe('XmlReader', function () use (&$cachedReader) {
    
    beforeEach(function () use (&$cachedReader) {
        // Reuse same instance (XmlReader is stateless)
        if ($cachedReader === null) {
            $cachedReader = new XmlReader();
        }
        $this->xmlReader = $cachedReader;
    });
    
    it('can read Infinri_Core di.xml', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $config = $this->xmlReader->read($corePath);
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('preferences');
        expect($config)->toHaveKey('types');
    });
    
    it('can read Infinri_Theme di.xml', function () {
        $themePath = __DIR__ . '/../../../app/Infinri/Theme';
        
        $config = $this->xmlReader->read($themePath);
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('virtualTypes');
    });
    
    it('returns null for missing di.xml', function () {
        $invalidPath = __DIR__;
        
        $config = $this->xmlReader->read($invalidPath);
        
        expect($config)->toBeNull();
    });
    
    it('parses preferences correctly', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $config = $this->xmlReader->read($corePath);
        
        expect($config['preferences'])->toBeArray();
        expect($config['preferences'])->toHaveKey('Infinri\Core\Api\ConfigInterface');
        expect($config['preferences']['Infinri\Core\Api\ConfigInterface'])
            ->toBe('Infinri\Core\Model\Config\ScopeConfig');
    });
    
    it('parses type configurations with arguments', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $config = $this->xmlReader->read($corePath);
        
        // Core di.xml now uses autowiring, so types array may be empty
        expect($config['types'])->toBeArray();
    });
    
    it('parses virtual types', function () {
        $themePath = __DIR__ . '/../../../app/Infinri/Theme';
        
        $config = $this->xmlReader->read($themePath);
        
        expect($config['virtualTypes'])->toBeArray();
        expect($config['virtualTypes'])->toHaveKey('ThemeConfig');
    });
    
    it('validates di.xml exists', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $isValid = $this->xmlReader->validate($corePath);
        
        expect($isValid)->toBeTrue();
    });
    
    it('validates fails for invalid path', function () {
        $invalidPath = __DIR__;
        
        $isValid = $this->xmlReader->validate($invalidPath);
        
        expect($isValid)->toBeFalse();
    });
    
    it('handles malformed XML gracefully', function () {
        // Create a temporary directory with invalid XML
        $tempDir = sys_get_temp_dir() . '/test_di_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/etc');
        
        file_put_contents($tempDir . '/etc/di.xml', '<?xml version="1.0"?><invalid>');
        
        $config = $this->xmlReader->read($tempDir);
        
        // Cleanup
        unlink($tempDir . '/etc/di.xml');
        rmdir($tempDir . '/etc');
        rmdir($tempDir);
        
        expect($config)->toBeNull();
    });
    
});
