<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Cache;

use Infinri\Core\Api\CacheInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;

/**
 * Wrapper around Symfony Cache for simplified cache operations
 */
class Pool implements CacheInterface
{
    /**
     * Symfony Cache instance
     *
     * @var SymfonyCacheInterface
     */
    private SymfonyCacheInterface $cache;

    /**
     * Default TTL in seconds
     *
     * @var int
     */
    private int $defaultTtl;

    /**
     * Constructor
     *
     * @param SymfonyCacheInterface|null $cache Symfony cache instance
     * @param int $defaultTtl Default TTL in seconds (0 = forever)
     */
    public function __construct(?SymfonyCacheInterface $cache = null, int $defaultTtl = 3600)
    {
        $this->cache = $cache ?? new FilesystemAdapter();
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Get item from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     * @throws InvalidArgumentException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    /**
     * Store item in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = use default)
     * @return bool True on success
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $item = $this->cache->getItem($key);
        $item->set($value);

        if ($ttl !== null) {
            $item->expiresAfter($ttl);
        } elseif ($this->defaultTtl > 0) {
            $item->expiresAfter($this->defaultTtl);
        }

        return $this->cache->save($item);
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if exists
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        return $this->cache->getItem($key)->isHit();
    }

    /**
     * Delete item from cache
     *
     * @param string $key Cache key
     * @return bool True on success
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Clear all cache
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Get multiple items from cache
     *
     * @param array<string> $keys Array of cache keys
     * @param mixed $default Default value for missing items
     * @return array<string, mixed> Array of values indexed by keys
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $items = $this->cache->getItems($keys);
        $results = [];

        foreach ($items as $key => $item) {
            $results[$key] = $item->isHit() ? $item->get() : $default;
        }

        return $results;
    }

    /**
     * Store multiple items in cache
     *
     * @param array<string, mixed> $values Array of key-value pairs
     * @param int|null $ttl Time to live in seconds
     * @return bool True on success
     * @throws InvalidArgumentException
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Delete multiple items from cache
     *
     * @param array<string> $keys Array of cache keys
     * @return bool True on success
     * @throws InvalidArgumentException
     */
    public function deleteMultiple(array $keys): bool
    {
        return $this->cache->deleteItems($keys);
    }

    /**
     * Get underlying Symfony cache instance
     *
     * @return SymfonyCacheInterface
     */
    public function getCache(): SymfonyCacheInterface
    {
        return $this->cache;
    }

    /**
     * Set default TTL
     *
     * @param int $ttl TTL in seconds
     * @return void
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Get default TTL
     *
     * @return int TTL in seconds
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }
}
