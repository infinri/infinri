<?php

declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Provides caching capabilities for the application
 */
interface CacheInterface
{
    /**
     * Get item from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store item in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live in seconds (null = forever)
     * @return bool True on success
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Delete item from cache
     *
     * @param string $key Cache key
     * @return bool True on success
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache
     *
     * @return bool True on success
     */
    public function clear(): bool;

    /**
     * Get multiple items from cache
     *
     * @param array $keys Array of cache keys
     * @param mixed $default Default value for missing items
     * @return array Array of values indexed by keys
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Store multiple items in cache
     *
     * @param array $values Array of key-value pairs
     * @param int|null $ttl Time to live in seconds
     * @return bool True on success
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Delete multiple items from cache
     *
     * @param array $keys Array of cache keys
     * @return bool True on success
     */
    public function deleteMultiple(array $keys): bool;
}
