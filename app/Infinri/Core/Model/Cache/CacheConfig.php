<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Cache;

/**
 * Centralized cache configuration with intelligent adapter selection
 * Implements Redis-first strategy with automatic fallback.
 */
class CacheConfig
{
    /**
     * Get optimal cache adapter based on environment and availability.
     *
     * @return string Optimal adapter name
     */
    public static function getOptimalAdapter(): string
    {
        // Priority order: Redis > APCu > Filesystem
        return match (true) {
            self::isRedisAvailable() => 'redis',
            self::isApcuAvailable() => 'apcu',
            default => 'filesystem'
        };
    }

    /**
     * Get environment-appropriate TTL.
     *
     * @return int TTL in seconds
     */
    public static function getDefaultTtl(): int
    {
        return self::isProduction() ? 3600 : 300; // 1 hour prod, 5 min dev
    }

    /**
     * Get cache prefix from environment.
     *
     * @return string Cache prefix
     */
    public static function getCachePrefix(): string
    {
        return $_ENV['CACHE_PREFIX'] ?? 'infinri_';
    }

    /**
     * Check if Redis is available and configured.
     *
     * @return bool True if Redis is available
     */
    public static function isRedisAvailable(): bool
    {
        // Check if Redis extension is loaded
        if (! \extension_loaded('redis')) {
            return false;
        }

        // Check if Redis is configured in environment
        $driver = $_ENV['CACHE_DRIVER'] ?? 'file';
        if ('redis' !== $driver) {
            return false;
        }

        // Test Redis connection
        return self::canConnectToRedis();
    }

    /**
     * Check if APCu is available.
     *
     * @return bool True if APCu is available
     */
    public static function isApcuAvailable(): bool
    {
        return \extension_loaded('apcu')
               && \ini_get('apc.enabled')
               && ! self::isCliMode();
    }

    /**
     * Check if running in production environment.
     *
     * @return bool True if production
     */
    public static function isProduction(): bool
    {
        $env = $_ENV['APP_ENV'] ?? 'development';

        return \in_array($env, ['production', 'prod'], true);
    }

    /**
     * Check if running in CLI mode (APCu not available in CLI).
     *
     * @return bool True if CLI mode
     */
    public static function isCliMode(): bool
    {
        return \PHP_SAPI === 'cli';
    }

    /**
     * Test Redis connection.
     *
     * @return bool True if can connect to Redis
     */
    private static function canConnectToRedis(): bool
    {
        static $canConnect = null;

        if (null !== $canConnect) {
            return $canConnect;
        }

        try {
            $redis = new \Redis();
            $host = $_ENV['REDIS_HOST'] ?? 'localhost';
            $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);
            $timeout = 1; // 1 second timeout for connection test

            $connected = $redis->connect($host, $port, $timeout);

            if ($connected) {
                // Test authentication if password is set
                $password = $_ENV['REDIS_PASSWORD'] ?? '';
                if ('' !== $password) {
                    $redis->auth($password);
                }

                // Test basic operation
                $redis->ping();
                $redis->close();

                $canConnect = true;
            } else {
                $canConnect = false;
            }
        } catch (\Exception $e) {
            // Note: Using error_log here instead of Logger to avoid circular dependency
            // since Logger might use caching which depends on this class
            error_log('Redis connection test failed: ' . $e->getMessage());
            $canConnect = false;
        }

        return $canConnect;
    }

    /**
     * Get Redis configuration array.
     *
     * @return array Redis configuration
     */
    public static function getRedisConfig(): array
    {
        return [
            'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? '',
            'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
            'timeout' => 2.0, // Connection timeout
            'read_timeout' => 2.0, // Read timeout
        ];
    }

    /**
     * Get cache configuration for specific cache type.
     *
     * @param string $cacheType Cache type (config, layout, block_html, etc.)
     *
     * @return array Cache configuration
     */
    public static function getCacheTypeConfig(string $cacheType): array
    {
        $baseConfig = [
            'adapter' => self::getOptimalAdapter(),
            'ttl' => self::getDefaultTtl(),
            'prefix' => self::getCachePrefix() . $cacheType . '_',
        ];

        // Override TTL for specific cache types
        $ttlOverrides = [
            'config' => self::isProduction() ? 7200 : 300,    // 2 hours prod, 5 min dev
            'layout' => self::isProduction() ? 3600 : 300,    // 1 hour prod, 5 min dev
            'block_html' => self::isProduction() ? 1800 : 60, // 30 min prod, 1 min dev
            'full_page' => self::isProduction() ? 900 : 30,   // 15 min prod, 30 sec dev
            'translation' => self::isProduction() ? 3600 : 300, // 1 hour prod, 5 min dev
            'asset' => self::isProduction() ? 86400 : 300,    // 24 hours prod, 5 min dev
        ];

        if (isset($ttlOverrides[$cacheType])) {
            $baseConfig['ttl'] = $ttlOverrides[$cacheType];
        }

        return $baseConfig;
    }

    /**
     * Check if specific cache type is enabled.
     *
     * @param string $cacheType Cache type
     *
     * @return bool True if enabled
     */
    public static function isCacheTypeEnabled(string $cacheType): bool
    {
        $envKey = 'CACHE_' . strtoupper($cacheType) . '_ENABLED';
        $enabled = $_ENV[$envKey] ?? 'true';

        // Respect development disable cache setting
        if (($_ENV['DEV_DISABLE_CACHE'] ?? 'false') === 'true' && ! self::isProduction()) {
            return false;
        }

        return filter_var($enabled, \FILTER_VALIDATE_BOOLEAN);
    }
}
