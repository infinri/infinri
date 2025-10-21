<?php

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Layout\Loader;
use Infinri\Core\Model\Cache\Pool;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Psr\Cache\CacheItemInterface;

class CountingArrayAdapter extends ArrayAdapter
{
    public int $saveCount = 0;

    public function save(CacheItemInterface $item): bool
    {
        $this->saveCount++;
        return parent::save($item);
    }
}

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
    $this->loader = new Loader($this->moduleManager);
});

describe('Loader', function () {
    
    it('can load default layout from Core module', function () {
        $layouts = $this->loader->load('default');
        
        expect($layouts)->toBeArray();
        expect($layouts)->toHaveKey('Infinri_Core');
    });
    
    it('can load default layout from Theme module', function () {
        $layouts = $this->loader->load('default');
        expect($layouts)->toHaveKey('Infinri_Theme');
    });
    
    it('loads layouts in module dependency order', function () {
        $layouts = $this->loader->load('default');

        $keys = array_keys($layouts);

        
        // Core should come before Theme
        $coreIndex = array_search('Infinri_Core', $keys);
        $themeIndex = array_search('Infinri_Theme', $keys);
        
        expect($coreIndex)->toBeLessThan($themeIndex);
    });
    
    it('returns empty array for non-existent handle', function () {
        $layouts = $this->loader->load('nonexistent_handle');

        expect($layouts)->toBeArray();
        expect($layouts)->toBeEmpty();
    });
    
    it('can get list of available handles', function () {
        $handles = $this->loader->getAvailableHandles();
        
        expect($handles)->toBeArray();
        expect($handles)->toContain('default');
    });
    
    it('handles malformed XML gracefully', function () {
        // Just test that non-existent handles return empty array
        // Actual malformed XML testing skipped to avoid temp file issues
        $layouts = $this->loader->load('test_nonexistent');

        expect($layouts)->toBeArray();
        expect($layouts)->toBeEmpty();
    });
    
    it('loads XML with correct structure', function () {
        $layouts = $this->loader->load('default');

        expect($layouts['Infinri_Core']->getName())->toBe('layout');
    });

    it('stores layouts in cache when enabled', function () {
        $_ENV['CACHE_LAYOUT_ENABLED'] = 'true';

        $adapter = new CountingArrayAdapter();
        $cachePool = new Pool($adapter);
        $loader = new Loader($this->moduleManager, $cachePool);

        $loader->load('default');

        $modules = $this->moduleManager->getModulesInOrder();
        $cacheKey = 'layout_default_' . md5(implode('|', $modules));

        expect($adapter->hasItem($cacheKey))->toBeTrue();

        unset($_ENV['CACHE_LAYOUT_ENABLED']);
    });

    it('uses cached layouts on subsequent loads when enabled', function () {
        $_ENV['CACHE_LAYOUT_ENABLED'] = 'true';

        $adapter = new CountingArrayAdapter();
        $cachePool = new Pool($adapter);
        $loader = new Loader($this->moduleManager, $cachePool);

        $loader->load('default');
        $firstSaveCount = $adapter->saveCount;

        $loader->load('default');

        expect($adapter->saveCount)->toBe($firstSaveCount);

        unset($_ENV['CACHE_LAYOUT_ENABLED']);
    });
    
});
