<?php

declare(strict_types=1);

use Infinri\Core\Model\Cache\TypeList;
use Infinri\Core\Model\Cache\Factory;
use Infinri\Core\Model\Cache\Pool;

describe('Cache TypeList', function () {
    
    beforeEach(function () {
        $factory = new Factory(null, 'array', 1800);
        $this->typeList = new TypeList($factory);
    });
    
    it('has predefined cache types', function () {
        $types = $this->typeList->getTypes();
        
        expect($types)->toHaveKey(TypeList::TYPE_CONFIG);
        expect($types)->toHaveKey(TypeList::TYPE_LAYOUT);
        expect($types)->toHaveKey(TypeList::TYPE_BLOCK_HTML);
        expect($types)->toHaveKey(TypeList::TYPE_FULL_PAGE);
        expect($types)->toHaveKey(TypeList::TYPE_TRANSLATION);
        expect($types)->toHaveKey(TypeList::TYPE_ASSET);
    });
    
    it('config cache is enabled by default', function () {
        expect($this->typeList->isEnabled(TypeList::TYPE_CONFIG))->toBeTrue();
    });
    
    it('layout cache is enabled by default', function () {
        expect($this->typeList->isEnabled(TypeList::TYPE_LAYOUT))->toBeTrue();
    });
    
    it('block_html cache is enabled by default', function () {
        expect($this->typeList->isEnabled(TypeList::TYPE_BLOCK_HTML))->toBeTrue();
    });
    
    it('full_page cache is disabled by default', function () {
        expect($this->typeList->isEnabled(TypeList::TYPE_FULL_PAGE))->toBeFalse();
    });
    
    it('can get cache pool for enabled type', function () {
        $cache = $this->typeList->getCache(TypeList::TYPE_CONFIG);
        
        expect($cache)->toBeInstanceOf(Pool::class);
    });
    
    it('returns null for disabled cache type', function () {
        $cache = $this->typeList->getCache(TypeList::TYPE_FULL_PAGE);
        
        expect($cache)->toBeNull();
    });
    
    it('can enable a cache type', function () {
        $this->typeList->enable(TypeList::TYPE_FULL_PAGE);
        
        expect($this->typeList->isEnabled(TypeList::TYPE_FULL_PAGE))->toBeTrue();
    });
    
    it('can disable a cache type', function () {
        $this->typeList->disable(TypeList::TYPE_CONFIG);
        
        expect($this->typeList->isEnabled(TypeList::TYPE_CONFIG))->toBeFalse();
    });
    
    it('can clear specific cache type', function () {
        $cache = $this->typeList->getCache(TypeList::TYPE_CONFIG);
        $cache->set('test_key', 'test_value');
        
        $this->typeList->clear(TypeList::TYPE_CONFIG);
        
        expect($cache->has('test_key'))->toBeFalse();
    });
    
    it('can clear all cache types', function () {
        $configCache = $this->typeList->getCache(TypeList::TYPE_CONFIG);
        $layoutCache = $this->typeList->getCache(TypeList::TYPE_LAYOUT);
        
        $configCache->set('config_key', 'value');
        $layoutCache->set('layout_key', 'value');
        
        $this->typeList->clearAll();
        
        expect($configCache->has('config_key'))->toBeFalse();
        expect($layoutCache->has('layout_key'))->toBeFalse();
    });
    
    it('can get all cache types', function () {
        $types = $this->typeList->getTypes();
        
        expect($types)->toBeArray();
        expect(count($types))->toBe(6);
    });
    
    it('can get enabled cache types', function () {
        $enabled = $this->typeList->getEnabledTypes();
        
        expect($enabled)->toBeArray();
        expect($enabled)->toContain(TypeList::TYPE_CONFIG);
        expect($enabled)->toContain(TypeList::TYPE_LAYOUT);
        expect($enabled)->not->toContain(TypeList::TYPE_FULL_PAGE);
    });
    
    it('can get cache type metadata', function () {
        $metadata = $this->typeList->getTypeMetadata(TypeList::TYPE_CONFIG);
        
        expect($metadata)->toBeArray();
        expect($metadata)->toHaveKey('label');
        expect($metadata)->toHaveKey('description');
        expect($metadata['label'])->toBe('Configuration');
    });
    
    it('returns null for non-existent cache type metadata', function () {
        $metadata = $this->typeList->getTypeMetadata('nonexistent');
        
        expect($metadata)->toBeNull();
    });
    
    it('can check if cache type exists', function () {
        expect($this->typeList->hasType(TypeList::TYPE_CONFIG))->toBeTrue();
        expect($this->typeList->hasType('nonexistent'))->toBeFalse();
    });
    
    it('returns false for non-existent cache type', function () {
        expect($this->typeList->isEnabled('nonexistent'))->toBeFalse();
    });
    
    it('enable does nothing for non-existent type', function () {
        $this->typeList->enable('nonexistent');
        
        expect($this->typeList->isEnabled('nonexistent'))->toBeFalse();
    });
    
    it('disable does nothing for non-existent type', function () {
        $this->typeList->disable('nonexistent');
        
        // Should not throw exception
        expect(true)->toBeTrue();
    });
    
    it('all types have required metadata fields', function () {
        $types = $this->typeList->getTypes();
        
        foreach ($types as $type => $metadata) {
            expect($metadata)->toHaveKey('label');
            expect($metadata)->toHaveKey('description');
            expect($metadata)->toHaveKey('enabled');
            expect($metadata['label'])->toBeString();
            expect($metadata['description'])->toBeString();
            expect($metadata['enabled'])->toBeIn([true, false]);
        }
    });
    
    it('can toggle cache type status', function () {
        // Initially enabled
        expect($this->typeList->isEnabled(TypeList::TYPE_CONFIG))->toBeTrue();
        
        // Disable
        $this->typeList->disable(TypeList::TYPE_CONFIG);
        expect($this->typeList->isEnabled(TypeList::TYPE_CONFIG))->toBeFalse();
        
        // Enable again
        $this->typeList->enable(TypeList::TYPE_CONFIG);
        expect($this->typeList->isEnabled(TypeList::TYPE_CONFIG))->toBeTrue();
    });
});
