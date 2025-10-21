<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Cache\Pool;

/**
 * Config Loader
 * 
 * Loads and merges configuration from all enabled modules.
 * Supports caching for improved performance.
 */
class Loader
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly Reader $configReader,
        private readonly ?Pool $cachePool = null
    ) {
    }

    /**
     * Load configuration from all enabled modules
     *
     * @return array<string, mixed> Merged configuration array
     */
    public function load(): array
    {
        // Check cache if available
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey();
            $cached = $this->cachePool->get($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Load from files
        $config = $this->loadFromFiles();
        
        // Store in cache
        if ($this->isCacheEnabled()) {
            $this->cachePool->set($this->getCacheKey(), $config, self::CACHE_TTL);
        }
        
        return $config;
    }

    /**
     * Load configuration from files
     *
     * @return array<string, mixed>
     */
    private function loadFromFiles(): array
    {
        $config = [];
        
        // Get modules in dependency order
        $modules = $this->moduleManager->getModulesInOrder();
        
        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            
            if (!$moduleData || !isset($moduleData['path'])) {
                continue;
            }
            
            $moduleConfig = $this->configReader->read($moduleData['path']);
            
            if ($moduleConfig !== null) {
                $config = $this->mergeConfig($config, $moduleConfig);
            }
        }
        
        return $config;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    private function isCacheEnabled(): bool
    {
        return $this->cachePool !== null 
            && filter_var($_ENV['CACHE_CONFIG_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get cache key
     *
     * @return string
     */
    private function getCacheKey(): string
    {
        $modules = $this->moduleManager->getModulesInOrder();
        return 'config_merged_' . md5(implode('|', $modules));
    }

    /**
     * Merge two configuration arrays recursively
     *
     * @param array<string, mixed> $base Base configuration
     * @param array<string, mixed> $new New configuration to merge
     * @return array<string, mixed>
     */
    private function mergeConfig(array $base, array $new): array
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // Both are arrays - merge recursively
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                // Overwrite with new value
                $base[$key] = $value;
            }
        }
        
        return $base;
    }

    /**
     * Load configuration for a specific scope
     *
     * @param string $scope Scope type (default, website, store)
     * @return array<string, mixed>
     */
    public function loadByScope(string $scope): array
    {
        $allConfig = $this->load();
        
        // Return configuration for the specific scope if it exists
        return $allConfig[$scope] ?? [];
    }
}
