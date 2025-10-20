<?php

declare(strict_types=1);

use Infinri\Core\Model\Config;
use Infinri\Core\Model\Config\ScopeConfig;

beforeEach(function () {
    // Create a mock Config object
    $this->configMock = Mockery::mock(Config::class);
    $this->scopeConfig = new ScopeConfig($this->configMock);
});

afterEach(function () {
    Mockery::close();
});

describe('ScopeConfig', function () {
    
    it('can get configuration value as string', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('web/site/name', 'default', 0)
            ->once()
            ->andReturn('Test Site');
        
        $value = $this->scopeConfig->getValue('web/site/name');
        
        expect($value)->toBe('Test Site');
    });
    
    it('can check if flag is set', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('dev/debug/enabled', 'default', 0)
            ->once()
            ->andReturn('1');
        
        $result = $this->scopeConfig->isSetFlag('dev/debug/enabled');
        
        expect($result)->toBeTrue();
    });
    
    it('can get value as integer', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('general/page/limit', 'default', 0)
            ->once()
            ->andReturn('25');
        
        $value = $this->scopeConfig->getInt('general/page/limit');
        
        expect($value)->toBe(25);
    });
    
    it('can get value as float', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('price/tax/rate', 'default', 0)
            ->once()
            ->andReturn('0.075');
        
        $value = $this->scopeConfig->getFloat('price/tax/rate');
        
        expect($value)->toBe(0.075);
    });
    
    it('can get value as boolean', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('feature/enabled', 'default', 0)
            ->once()
            ->andReturn('true');
        
        $value = $this->scopeConfig->getBool('feature/enabled');
        
        expect($value)->toBeTrue();
    });
    
    it('can get value as array from JSON', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('list/items', 'default', 0)
            ->once()
            ->andReturn('["item1","item2","item3"]');
        
        $value = $this->scopeConfig->getArray('list/items');
        
        expect($value)->toBe(['item1', 'item2', 'item3']);
    });
    
    it('handles null values gracefully', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('nonexistent/path', 'default', 0)
            ->once()
            ->andReturn(false);
        
        $value = $this->scopeConfig->getValue('nonexistent/path');
        
        expect($value)->toBeNull();
    });
    
    it('supports custom scopes', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('web/site/name', 'website', 1)
            ->once()
            ->andReturn('Website Specific Name');
        
        $value = $this->scopeConfig->getValue('web/site/name', 'website', 1);
        
        expect($value)->toBe('Website Specific Name');
    });
    
    it('returns empty array for invalid JSON', function () {
        $this->configMock->shouldReceive('getValue')
            ->with('invalid/json', 'default', 0)
            ->once()
            ->andReturn('not valid json');
        
        $value = $this->scopeConfig->getArray('invalid/json');
        
        expect($value)->toBe([]);
    });
});
