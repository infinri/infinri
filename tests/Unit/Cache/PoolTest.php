<?php

declare(strict_types=1);

use Infinri\Core\Model\Cache\Pool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

describe('Cache Pool', function () {
    
    beforeEach(function () {
        // Use ArrayAdapter for testing (in-memory, no filesystem)
        $this->pool = new Pool(new ArrayAdapter(), 3600);
    });
    
    it('can set and get a value', function () {
        $this->pool->set('test_key', 'test_value');
        
        $value = $this->pool->get('test_key');
        
        expect($value)->toBe('test_value');
    });
    
    it('returns default value when key does not exist', function () {
        $value = $this->pool->get('nonexistent_key', 'default');
        
        expect($value)->toBe('default');
    });
    
    it('returns null as default when key does not exist', function () {
        $value = $this->pool->get('nonexistent_key');
        
        expect($value)->toBeNull();
    });
    
    it('can check if key exists', function () {
        $this->pool->set('existing_key', 'value');
        
        expect($this->pool->has('existing_key'))->toBeTrue();
        expect($this->pool->has('nonexistent_key'))->toBeFalse();
    });
    
    it('can delete a value', function () {
        $this->pool->set('key_to_delete', 'value');
        
        expect($this->pool->has('key_to_delete'))->toBeTrue();
        
        $this->pool->delete('key_to_delete');
        
        expect($this->pool->has('key_to_delete'))->toBeFalse();
    });
    
    it('can clear all cache', function () {
        $this->pool->set('key1', 'value1');
        $this->pool->set('key2', 'value2');
        $this->pool->set('key3', 'value3');
        
        $this->pool->clear();
        
        expect($this->pool->has('key1'))->toBeFalse();
        expect($this->pool->has('key2'))->toBeFalse();
        expect($this->pool->has('key3'))->toBeFalse();
    });
    
    it('can set value with custom TTL', function () {
        $this->pool->set('ttl_key', 'value', 60);
        
        expect($this->pool->get('ttl_key'))->toBe('value');
    });
    
    it('can set value with zero TTL (forever)', function () {
        $pool = new Pool(new ArrayAdapter(), 0);
        $pool->set('forever_key', 'value');
        
        expect($pool->get('forever_key'))->toBe('value');
    });
    
    it('can get multiple values', function () {
        $this->pool->set('key1', 'value1');
        $this->pool->set('key2', 'value2');
        $this->pool->set('key3', 'value3');
        
        $values = $this->pool->getMultiple(['key1', 'key2', 'key3']);
        
        expect($values)->toBe([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);
    });
    
    it('returns default for missing keys in getMultiple', function () {
        $this->pool->set('key1', 'value1');
        
        $values = $this->pool->getMultiple(['key1', 'missing_key'], 'default');
        
        expect($values['key1'])->toBe('value1');
        expect($values['missing_key'])->toBe('default');
    });
    
    it('can set multiple values', function () {
        $result = $this->pool->setMultiple([
            'multi1' => 'value1',
            'multi2' => 'value2',
            'multi3' => 'value3',
        ]);
        
        expect($result)->toBeTrue();
        expect($this->pool->get('multi1'))->toBe('value1');
        expect($this->pool->get('multi2'))->toBe('value2');
        expect($this->pool->get('multi3'))->toBe('value3');
    });
    
    it('can set multiple values with TTL', function () {
        $this->pool->setMultiple([
            'ttl1' => 'value1',
            'ttl2' => 'value2',
        ], 120);
        
        expect($this->pool->get('ttl1'))->toBe('value1');
        expect($this->pool->get('ttl2'))->toBe('value2');
    });
    
    it('can delete multiple values', function () {
        $this->pool->set('del1', 'value1');
        $this->pool->set('del2', 'value2');
        $this->pool->set('del3', 'value3');
        
        $this->pool->deleteMultiple(['del1', 'del2']);
        
        expect($this->pool->has('del1'))->toBeFalse();
        expect($this->pool->has('del2'))->toBeFalse();
        expect($this->pool->has('del3'))->toBeTrue();
    });
    
    it('can cache different data types', function () {
        $this->pool->set('string', 'text');
        $this->pool->set('integer', 123);
        $this->pool->set('float', 45.67);
        $this->pool->set('array', ['a', 'b', 'c']);
        $this->pool->set('object', (object)['key' => 'value']);
        $this->pool->set('boolean', true);
        $this->pool->set('null', null);
        
        expect($this->pool->get('string'))->toBe('text');
        expect($this->pool->get('integer'))->toBe(123);
        expect($this->pool->get('float'))->toBe(45.67);
        expect($this->pool->get('array'))->toBe(['a', 'b', 'c']);
        expect($this->pool->get('object'))->toEqual((object)['key' => 'value']);
        expect($this->pool->get('boolean'))->toBeTrue();
        expect($this->pool->get('null'))->toBeNull();
    });
    
    it('can get underlying cache instance', function () {
        $cache = $this->pool->getCache();
        
        expect($cache)->toBeInstanceOf(\Symfony\Contracts\Cache\CacheInterface::class);
    });
    
    it('can set and get default TTL', function () {
        $this->pool->setDefaultTtl(7200);
        
        expect($this->pool->getDefaultTtl())->toBe(7200);
    });
    
    it('uses default TTL when no TTL specified', function () {
        $pool = new Pool(new ArrayAdapter(), 1800);
        
        expect($pool->getDefaultTtl())->toBe(1800);
    });
    
    it('overwrites existing values', function () {
        $this->pool->set('overwrite_key', 'original');
        $this->pool->set('overwrite_key', 'updated');
        
        expect($this->pool->get('overwrite_key'))->toBe('updated');
    });
    
    it('handles empty key arrays in getMultiple', function () {
        $values = $this->pool->getMultiple([]);
        
        expect($values)->toBeEmpty();
    });
    
    it('handles empty arrays in setMultiple', function () {
        $result = $this->pool->setMultiple([]);
        
        expect($result)->toBeTrue();
    });
    
    it('handles empty arrays in deleteMultiple', function () {
        $result = $this->pool->deleteMultiple([]);
        
        expect($result)->toBeTrue();
    });
});
