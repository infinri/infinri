<?php

declare(strict_types=1);

use Infinri\Core\Model\Config\Reader;

// Static cache for reader instance
$cachedReader = null;

describe('ConfigReader', function () use (&$cachedReader) {
    
    beforeEach(function () use (&$cachedReader) {
        // Reuse same instance (Reader is stateless)
        if ($cachedReader === null) {
            $cachedReader = new Reader();
        }
        $this->configReader = $cachedReader;
    });
    
    it('can read Infinri_Core config.xml', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $config = $this->configReader->read($corePath);
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('default');
        expect($config['default'])->toHaveKey('system');
    });
    
    it('can read Infinri_Theme config.xml', function () {
        $themePath = __DIR__ . '/../../../app/Infinri/Theme';
        
        $config = $this->configReader->read($themePath);
        
        expect($config)->toBeArray();
        expect($config)->toHaveKey('default');
        expect($config['default'])->toHaveKey('theme');
    });
    
    it('returns null for missing config.xml', function () {
        $invalidPath = __DIR__;
        
        $config = $this->configReader->read($invalidPath);
        
        expect($config)->toBeNull();
    });
    
    it('validates config.xml exists', function () {
        $corePath = __DIR__ . '/../../../app/Infinri/Core';
        
        $isValid = $this->configReader->validate($corePath);
        
        expect($isValid)->toBeTrue();
    });
    
    it('validates fails for invalid path', function () {
        $invalidPath = __DIR__;
        
        $isValid = $this->configReader->validate($invalidPath);
        
        expect($isValid)->toBeFalse();
    });
    
    it('handles malformed XML gracefully', function () {
        // Create a temporary directory with invalid XML
        $tempDir = sys_get_temp_dir() . '/test_config_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/etc');
        
        file_put_contents($tempDir . '/etc/config.xml', '<?xml version="1.0"?><invalid>');
        
        $config = $this->configReader->read($tempDir);
        
        // Cleanup
        unlink($tempDir . '/etc/config.xml');
        rmdir($tempDir . '/etc');
        rmdir($tempDir);
        
        expect($config)->toBeNull();
    });
    
    it('converts XML to nested array structure', function () {
        $themePath = __DIR__ . '/../../../app/Infinri/Theme';
        
        $config = $this->configReader->read($themePath);
        
        expect($config['default']['theme']['general']['logo'])->toBe('Infinri_Theme::images/logo.svg');
        expect($config['default']['theme']['colors']['primary'])->toBe('#0066cc');
    });
    
});
