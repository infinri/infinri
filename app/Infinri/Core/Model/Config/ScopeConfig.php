<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use Infinri\Core\Model\Config;

/**
 * Scope Configuration
 * 
 * Provides convenient methods for retrieving configuration values
 * with proper type casting. Wraps the Config model.
 */
class ScopeConfig
{
    private Config $config;
    
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get configuration value as string
     *
     * @param string $path Configuration path (e.g., 'web/site/name')
     * @param string $scope Scope type (default, website, store)
     * @param int $scopeId Scope ID
     * @return string|null
     */
    public function getValue(string $path, string $scope = 'default', int $scopeId = 0): ?string
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
        return $value !== false ? (string)$value : null;
    }
    
    /**
     * Check if configuration path is set
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return bool
     */
    public function isSetFlag(string $path, string $scope = 'default', int $scopeId = 0): bool
    {
        $value = $this->config->getValue($path, $scope, $scopeId);
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
}
