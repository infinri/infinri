<?php

declare(strict_types=1);

use Infinri\Core\Model\Cache\Factory;
use Infinri\Core\Model\Cache\Pool;

describe('Cache Factory', function () {
    
    beforeEach(function () {
        $this->factory = new Factory(null, 'array', 1800);
    });
    
    it('can create a cache pool', function () {
        $pool = $this->factory->create('test_cache');
        
        expect($pool)->toBeInstanceOf(Pool::class);
    });
    
    it('returns same instance for same namespace', function () {
        $pool1 = $this->factory->create('test_cache');
        $pool2 = $this->factory->create('test_cache');
        
        expect($pool1)->toBe($pool2);
    });
    
    it('can create different cache pools for different namespaces', function () {
        $pool1 = $this->factory->create('cache1');
        $pool2 = $this->factory->create('cache2');
        
        expect($pool1)->not->toBe($pool2);
    });
    
    it('can create cache with custom adapter', function () {
        $pool = $this->factory->create('custom', 'array');
        
        expect($pool)->toBeInstanceOf(Pool::class);
    });
    
    it('can create cache with custom TTL', function () {
        $pool = $this->factory->create('ttl_test', 'array', 7200);
        
        expect($pool->getDefaultTtl())->toBe(7200);
    });
    
    it('uses default adapter when not specified', function () {
        $pool = $this->factory->create('default_adapter');
        
        expect($pool)->toBeInstanceOf(Pool::class);
    });
    
    it('uses default TTL when not specified', function () {
        $pool = $this->factory->create('default_ttl');
        
        expect($pool->getDefaultTtl())->toBe(1800);
    });
    
    it('can get existing cache instance', function () {
        $created = $this->factory->create('existing');
        $retrieved = $this->factory->get('existing');
        
        expect($retrieved)->toBe($created);
    });
    
    it('returns null for non-existent cache instance', function () {
        $retrieved = $this->factory->get('nonexistent');
        
        expect($retrieved)->toBeNull();
    });
    
    it('can clear all cache instances', function () {
        $pool1 = $this->factory->create('pool1');
        $pool2 = $this->factory->create('pool2');
        
        $pool1->set('key1', 'value1');
        $pool2->set('key2', 'value2');
        
        $this->factory->clearAll();
        
        expect($pool1->has('key1'))->toBeFalse();
        expect($pool2->has('key2'))->toBeFalse();
    });
    
    it('can set and get default adapter', function () {
        $this->factory->setDefaultAdapter('filesystem');
        
        expect($this->factory->getDefaultAdapter())->toBe('filesystem');
    });
    
    it('can check if filesystem adapter is available', function () {
        expect($this->factory->isAdapterAvailable('filesystem'))->toBeTrue();
    });
    
    it('can check if array adapter is available', function () {
        expect($this->factory->isAdapterAvailable('array'))->toBeTrue();
    });
    
    it('can check if apcu adapter is available', function () {
        $available = $this->factory->isAdapterAvailable('apcu');
        
        // APCu availability depends on extension being loaded and enabled
        expect($available)->toBeIn([true, false]);
    });
    
    it('returns false for invalid adapter', function () {
        expect($this->factory->isAdapterAvailable('invalid'))->toBeFalse();
    });
    
    it('throws exception for invalid adapter type', function () {
        expect(fn() => $this->factory->create('test', 'invalid_adapter'))
            ->toThrow(InvalidArgumentException::class, 'Invalid cache adapter');
    });
    
    it('creates filesystem adapter successfully', function () {
        $pool = $this->factory->create('filesystem_test', 'filesystem');
        
        expect($pool)->toBeInstanceOf(Pool::class);
        
        // Test it works
        $pool->set('test_key', 'test_value');
        expect($pool->get('test_key'))->toBe('test_value');
    });
    
    it('creates array adapter successfully', function () {
        $pool = $this->factory->create('array_test', 'array');
        
        expect($pool)->toBeInstanceOf(Pool::class);
    });
    
    it('creates different instances for different adapters same namespace', function () {
        $arrayPool = $this->factory->create('mixed', 'array');
        $filesystemPool = $this->factory->create('mixed', 'filesystem');
        
        expect($arrayPool)->not->toBe($filesystemPool);
    });
});
