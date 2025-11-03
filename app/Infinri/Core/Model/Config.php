<?php
declare(strict_types=1);

namespace Infinri\Core\Model;

use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Manages system configuration stored in core_config_data table
 * Similar to Magento's config system with scopes
 */
class Config
{
    private const TABLE_NAME = 'core_config_data';
    
    private Connection $connection;
    private array $cache = [];
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * Get configuration value
     *
     * @param string $path Configuration path (e.g., 'web/site/name')
     * @param string $scope Scope type (default, website, store)
     * @param int $scopeId Scope ID
     * @return mixed
     */
    public function getValue(string $path, string $scope = 'default', int $scopeId = 0): mixed
    {
        $cacheKey = "{$scope}_{$scopeId}_{$path}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $sql = "SELECT value FROM " . self::TABLE_NAME . " 
                WHERE scope = ? AND scope_id = ? AND path = ? 
                LIMIT 1";
        
        $value = $this->connection->fetchOne($sql, [$scope, $scopeId, $path]);
        
        $this->cache[$cacheKey] = $value;
        
        return $value;
    }
    
    /**
     * Save configuration value
     *
     * @param string $path Configuration path
     * @param mixed $value Configuration value
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return void
     */
    public function saveValue(string $path, mixed $value, string $scope = 'default', int $scopeId = 0): void
    {
        $stringValue = $value === null ? null : (string)$value;
        
        // Check if config exists
        $sql = "SELECT config_id FROM " . self::TABLE_NAME . " 
                WHERE scope = ? AND scope_id = ? AND path = ?";
        
        $configId = $this->connection->fetchOne($sql, [$scope, $scopeId, $path]);
        
        if ($configId) {
            // Update existing
            $this->connection->update(
                self::TABLE_NAME,
                ['value' => $stringValue],
                'config_id = ?',
                [$configId]
            );
        } else {
            // Insert new
            $this->connection->insert(self::TABLE_NAME, [
                'scope' => $scope,
                'scope_id' => $scopeId,
                'path' => $path,
                'value' => $stringValue,
            ]);
        }
        
        // Clear cache
        $cacheKey = "{$scope}_{$scopeId}_{$path}";
        unset($this->cache[$cacheKey]);
    }
    
    /**
     * Delete configuration value
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return void
     */
    public function deleteValue(string $path, string $scope = 'default', int $scopeId = 0): void
    {
        $this->connection->delete(
            self::TABLE_NAME,
            'scope = ? AND scope_id = ? AND path = ?',
            [$scope, $scopeId, $path]
        );
        
        // Clear cache
        $cacheKey = "{$scope}_{$scopeId}_{$path}";
        unset($this->cache[$cacheKey]);
    }
    
    /**
     * Get all configuration values for a scope
     *
     * @param string $scope Scope type
     * @param int $scopeId Scope ID
     * @return array Array of path => value
     */
    public function getAllValues(string $scope = 'default', int $scopeId = 0): array
    {
        $sql = "SELECT path, value FROM " . self::TABLE_NAME . " 
                WHERE scope = ? AND scope_id = ?";
        
        $rows = $this->connection->fetchAll($sql, [$scope, $scopeId]);
        
        $config = [];
        foreach ($rows as $row) {
            $config[$row['path']] = $row['value'];
        }
        
        return $config;
    }
    
    /**
     * Clear configuration cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
