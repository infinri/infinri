<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use Infinri\Core\Model\Config;
use Infinri\Core\Model\Cache\Factory as CacheFactory;
use Infinri\Core\Api\CacheInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Provides convenient methods for retrieving configuration values
 * with proper type casting and caching support.
 */
class ScopeConfig
{
    private Config $config;
    private ?CacheInterface $cache = null;
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(Config $config, ?CacheFactory $cacheFactory = null)
    {
        $this->config = $config;
        
        // Initialize cache if factory provided
        if ($cacheFactory) {
            $this->cache = $cacheFactory->create('config', 'filesystem', self::CACHE_TTL);
        }
    }

    /**
     * Get configuration value as string
     *
     * @param string $path Configuration path (e.g., 'web/site/name')
     * @param string $scope Scope type (default, website, store)
     * @param int $scopeId Scope ID
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function getValue(string $path, string $scope = 'default', int $scopeId = 0): ?string
    {
        $cacheKey = $this->getCacheKey($path, $scope, $scopeId);
        
        // Try cache first
        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        // Get from database
        $value = $this->config->getValue($path, $scope, $scopeId);
        $result = $value !== false ? (string)$value : null;
        
        // Cache the result
        if ($this->cache && $result !== null) {
            $this->cache->set($cacheKey, $result);
        }
        
        return $result;
    }

    /**
     * Check if configuration path is set
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isSetFlag(string $path, string $scope = 'default', int $scopeId = 0): bool
    {
        $value = $this->getValue($path, $scope, $scopeId);
        return (bool)$value;
    }
    
    /**
     * Get configuration value as integer
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return int
     */
    public function getInt(string $path, string $scope = 'default', int $scopeId = 0): int
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
        return (int)$value;
    }
    
    /**
     * Get configuration value as float
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return float
     */
    public function getFloat(string $path, string $scope = 'default', int $scopeId = 0): float
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
        return (float)$value;
    }
    
    /**
     * Get configuration value as boolean
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return bool
     */
    public function getBool(string $path, string $scope = 'default', int $scopeId = 0): bool
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Get configuration value as array (JSON decoded)
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return array
     */
    public function getArray(string $path, string $scope = 'default', int $scopeId = 0): array
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
        
        if (empty($value)) {
            return [];
        }
        
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
    
    /**
     * Clear configuration cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        if ($this->cache) {
            $this->cache->clear();
        }
    }
    
    /**
     * Generate cache key
     * 
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    private function getCacheKey(string $path, string $scope, int $scopeId): string
    {
        return sprintf('config_%s_%s_%d', $path, $scope, $scopeId);
    }
}
