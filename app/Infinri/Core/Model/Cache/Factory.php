<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Cache;

use Infinri\Core\Helper\Logger;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Creates cache instances with Redis-first architecture
 * Supports: redis, filesystem, array (memory), apcu
 * Auto-detects optimal adapter with intelligent fallback.
 */
class Factory
{
    /**
     * Default cache directory.
     */
    private const CACHE_DIR = '/var/cache';

    /**
     * Base application path.
     */
    private string $basePath;

    /**
     * Default adapter type.
     */
    private string $defaultAdapter;

    /**
     * Default TTL.
     */
    private int $defaultTtl;

    /**
     * Cache instances.
     *
     * @var array<string, Pool>
     */
    private array $instances = [];

    /**
     * Constructor with intelligent adapter selection.
     *
     * @param string|null $basePath       Application base path
     * @param string|null $defaultAdapter Default adapter (null = auto-detect optimal)
     * @param int|null    $defaultTtl     Default TTL in seconds (null = environment-based)
     */
    public function __construct(
        ?string $basePath = null,
        ?string $defaultAdapter = null,
        ?int $defaultTtl = null
    ) {
        $this->basePath = $basePath ?? \dirname(__DIR__, 5);
        $this->defaultAdapter = $defaultAdapter ?? CacheConfig::getOptimalAdapter();
        $this->defaultTtl = $defaultTtl ?? CacheConfig::getDefaultTtl();
    }

    /**
     * Create a cache pool.
     *
     * @param string      $namespace Cache namespace
     * @param string|null $adapter   Adapter type (null = use default)
     * @param int|null    $ttl       TTL in seconds (null = use default)
     *
     * @return Pool Cache pool instance
     */
    public function create(string $namespace, ?string $adapter = null, ?int $ttl = null): Pool
    {
        $adapter = $adapter ?? $this->defaultAdapter;
        $ttl = $ttl ?? $this->defaultTtl;

        $cacheKey = $namespace . '_' . $adapter;

        if (isset($this->instances[$cacheKey])) {
            return $this->instances[$cacheKey];
        }

        $symfonyCache = $this->createAdapter($adapter, $namespace);
        $pool = new Pool($symfonyCache, $ttl);

        $this->instances[$cacheKey] = $pool;

        return $pool;
    }

    /**
     * Create Symfony cache adapter with intelligent fallback.
     *
     * @param string $type      Adapter type
     * @param string $namespace Namespace
     *
     * @return CacheInterface Symfony cache adapter
     */
    private function createAdapter(string $type, string $namespace): CacheInterface
    {
        return match ($type) {
            'redis' => $this->createRedisAdapter($namespace),
            'filesystem' => new FilesystemAdapter(
                $namespace,
                0,
                $this->basePath . self::CACHE_DIR
            ),
            'array' => new ArrayAdapter(),
            'apcu' => $this->createApcuAdapter($namespace),
            default => $this->createFallbackAdapter($namespace),
        };
    }

    /**
     * Create Redis adapter with automatic fallback.
     *
     * @param string $namespace Namespace
     *
     * @return CacheInterface Redis adapter or fallback
     */
    private function createRedisAdapter(string $namespace): CacheInterface
    {
        try {
            if (! CacheConfig::isRedisAvailable()) {
                return $this->createFallbackAdapter($namespace);
            }

            $config = CacheConfig::getRedisConfig();
            $redis = new \Redis();

            // Connect with timeout
            $connected = $redis->connect(
                $config['host'],
                $config['port'],
                $config['timeout']
            );

            if (! $connected) {
                throw new \Exception('Failed to connect to Redis');
            }

            // Set read timeout
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, $config['read_timeout']);

            // Authenticate if password is set
            if (! empty($config['password'])) {
                if (! $redis->auth($config['password'])) {
                    throw new \Exception('Redis authentication failed');
                }
            }

            // Select database
            if ($config['database'] > 0) {
                $redis->select($config['database']);
            }

            return new RedisAdapter($redis, $namespace);
        } catch (\Exception $e) {
            Logger::warning('Redis adapter creation failed, falling back to filesystem', [
                'error' => $e->getMessage(),
            ]);

            return $this->createFallbackAdapter($namespace);
        }
    }

    /**
     * Create APCu adapter with availability check.
     *
     * @param string $namespace Namespace
     *
     * @return CacheInterface APCu adapter or fallback
     */
    private function createApcuAdapter(string $namespace): CacheInterface
    {
        if (! CacheConfig::isApcuAvailable()) {
            return $this->createFallbackAdapter($namespace);
        }

        try {
            return new ApcuAdapter($namespace);
        } catch (\Exception $e) {
            Logger::warning('APCu adapter creation failed, falling back to filesystem', [
                'error' => $e->getMessage(),
            ]);

            return $this->createFallbackAdapter($namespace);
        }
    }

    /**
     * Create fallback adapter (always filesystem).
     *
     * @param string $namespace Namespace
     *
     * @return CacheInterface Filesystem adapter
     */
    private function createFallbackAdapter(string $namespace): CacheInterface
    {
        return new FilesystemAdapter(
            $namespace,
            0,
            $this->basePath . self::CACHE_DIR
        );
    }

    /**
     * Get existing cache instance.
     *
     * @param string $namespace Cache namespace
     *
     * @return Pool|null Cache pool or null if not created
     */
    public function get(string $namespace): ?Pool
    {
        $cacheKey = $namespace . '_' . $this->defaultAdapter;

        return $this->instances[$cacheKey] ?? null;
    }

    /**
     * Clear all cache instances.
     */
    public function clearAll(): void
    {
        foreach ($this->instances as $pool) {
            $pool->clear();
        }
    }

    /**
     * Set default adapter.
     *
     * @param string $adapter Adapter type
     */
    public function setDefaultAdapter(string $adapter): void
    {
        $this->defaultAdapter = $adapter;
    }

    /**
     * Get default adapter.
     *
     * @return string Adapter type
     */
    public function getDefaultAdapter(): string
    {
        return $this->defaultAdapter;
    }

    /**
     * Check if adapter is available.
     *
     * @param string $adapter Adapter type
     *
     * @return bool True if available
     */
    public function isAdapterAvailable(string $adapter): bool
    {
        return match ($adapter) {
            'redis' => CacheConfig::isRedisAvailable(),
            'filesystem' => true,
            'array' => true,
            'apcu' => CacheConfig::isApcuAvailable(),
            default => false,
        };
    }

    /**
     * Get current adapter being used.
     *
     * @return string Current adapter name
     */
    public function getCurrentAdapter(): string
    {
        return $this->defaultAdapter;
    }

    /**
     * Get adapter performance metrics.
     *
     * @return array Performance information
     */
    public function getAdapterMetrics(): array
    {
        $adapter = $this->getCurrentAdapter();

        return [
            'adapter' => $adapter,
            'available' => $this->isAdapterAvailable($adapter),
            'fallback_used' => $adapter !== CacheConfig::getOptimalAdapter(),
            'redis_available' => CacheConfig::isRedisAvailable(),
            'apcu_available' => CacheConfig::isApcuAvailable(),
            'instance_count' => \count($this->instances),
        ];
    }
}
