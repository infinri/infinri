<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Cache;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Creates cache instances based on configuration
 * Supports: filesystem, array (memory), apcu
 */
class Factory
{
    /**
     * Default cache directory
     */
    private const CACHE_DIR = '/var/cache';

    /**
     * Base application path
     *
     * @var string
     */
    private string $basePath;

    /**
     * Default adapter type
     *
     * @var string
     */
    private string $defaultAdapter;

    /**
     * Default TTL
     *
     * @var int
     */
    private int $defaultTtl;

    /**
     * Cache instances
     *
     * @var array<string, Pool>
     */
    private array $instances = [];

    /**
     * Constructor
     *
     * @param string|null $basePath Application base path
     * @param string $defaultAdapter Default adapter (filesystem, array, apcu)
     * @param int $defaultTtl Default TTL in seconds
     */
    public function __construct(
        ?string $basePath = null,
        string  $defaultAdapter = 'filesystem',
        int     $defaultTtl = 3600
    )
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 5);
        $this->defaultAdapter = $defaultAdapter;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Create a cache pool
     *
     * @param string $namespace Cache namespace
     * @param string|null $adapter Adapter type (null = use default)
     * @param int|null $ttl TTL in seconds (null = use default)
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
     * Create Symfony cache adapter
     *
     * @param string $type Adapter type
     * @param string $namespace Namespace
     * @return CacheInterface Symfony cache adapter
     * @throws \InvalidArgumentException|\Symfony\Component\Cache\Exception\CacheException If adapter type is invalid
     */
    private function createAdapter(string $type, string $namespace): CacheInterface
    {
        return match ($type) {
            'filesystem' => new FilesystemAdapter(
                $namespace,
                0,
                $this->basePath . self::CACHE_DIR
            ),
            'array' => new ArrayAdapter(),
            'apcu' => new ApcuAdapter($namespace),
            default => throw new \InvalidArgumentException("Invalid cache adapter: {$type}"),
        };
    }

    /**
     * Get existing cache instance
     *
     * @param string $namespace Cache namespace
     * @return Pool|null Cache pool or null if not created
     */
    public function get(string $namespace): ?Pool
    {
        $cacheKey = $namespace . '_' . $this->defaultAdapter;
        return $this->instances[$cacheKey] ?? null;
    }

    /**
     * Clear all cache instances
     *
     * @return void
     */
    public function clearAll(): void
    {
        foreach ($this->instances as $pool) {
            $pool->clear();
        }
    }

    /**
     * Set default adapter
     *
     * @param string $adapter Adapter type
     * @return void
     */
    public function setDefaultAdapter(string $adapter): void
    {
        $this->defaultAdapter = $adapter;
    }

    /**
     * Get default adapter
     *
     * @return string Adapter type
     */
    public function getDefaultAdapter(): string
    {
        return $this->defaultAdapter;
    }

    /**
     * Check if adapter is available
     *
     * @param string $adapter Adapter type
     * @return bool True if available
     */
    public function isAdapterAvailable(string $adapter): bool
    {
        return match ($adapter) {
            'filesystem' => true,
            'array' => true,
            'apcu' => extension_loaded('apcu') && ini_get('apc.enabled'),
            default => false,
        };
    }
}
