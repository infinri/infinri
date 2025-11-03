<?php

declare(strict_types=1);

namespace Infinri\Core\Service;

use Infinri\Core\App\Request;

/**
 * Prevents brute force attacks by limiting request rates
 *
 * Features:
 * - Per-IP rate limiting
 * - Per-user rate limiting
 * - Multiple time windows (minute, hour, day)
 * - Configurable limits per action
 * - Automatic cleanup of old records
 */
class RateLimiter
{
    /**
     * Rate limit storage (in-memory for simplicity)
     * In production, use Redis or database
     * Structure: [key => [timestamp => count]]
     */
    private static array $storage = [];

    /**
     * Default rate limits per action type
     */
    private const DEFAULT_LIMITS = [
        'login' => [
            'requests' => 5,      // Max requests
            'window' => 300,      // Time window in seconds (5 minutes)
        ],
        'api' => [
            'requests' => 60,     // Max requests
            'window' => 60,       // Time window in seconds (1 minute)
        ],
        'default' => [
            'requests' => 100,    // Max requests
            'window' => 60,       // Time window in seconds (1 minute)
        ],
    ];

    /**
     * Check if request is allowed under rate limit
     *
     * @param string $action Action being performed (e.g., 'login', 'api')
     * @param string $identifier Unique identifier (IP address, user ID, etc.)
     * @param int|null $maxRequests Max requests allowed (null = use default)
     * @param int|null $windowSeconds Time window in seconds (null = use default)
     * @return bool True if request is allowed
     */
    public function attempt(
        string $action,
        string $identifier,
        ?int   $maxRequests = null,
        ?int   $windowSeconds = null
    ): bool
    {
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['default'];

        $maxRequests = $maxRequests ?? $limits['requests'];
        $windowSeconds = $windowSeconds ?? $limits['window'];

        $key = $this->getKey($action, $identifier);
        $now = time();
        $windowStart = $now - $windowSeconds;

        // Clean up old entries
        $this->cleanup($key, $windowStart);

        // Count requests in current window
        $count = $this->countRequests($key, $windowStart);

        if ($count >= $maxRequests) {
            return false; // Rate limit exceeded
        }

        // Record this attempt
        $this->record($key, $now);

        return true; // Request allowed
    }

    /**
     * Check rate limit without recording attempt
     *
     * @param string $action Action being performed
     * @param string $identifier Unique identifier
     * @param int|null $maxRequests Max requests allowed
     * @param int|null $windowSeconds Time window in seconds
     * @return bool True if under rate limit
     */
    public function check(
        string $action,
        string $identifier,
        ?int   $maxRequests = null,
        ?int   $windowSeconds = null
    ): bool
    {
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['default'];

        $maxRequests = $maxRequests ?? $limits['requests'];
        $windowSeconds = $windowSeconds ?? $limits['window'];

        $key = $this->getKey($action, $identifier);
        $now = time();
        $windowStart = $now - $windowSeconds;

        $count = $this->countRequests($key, $windowStart);

        return $count < $maxRequests;
    }

    /**
     * Get number of remaining attempts
     *
     * @param string $action Action being performed
     * @param string $identifier Unique identifier
     * @param int|null $maxRequests Max requests allowed
     * @param int|null $windowSeconds Time window in seconds
     * @return int Number of attempts remaining
     */
    public function remaining(
        string $action,
        string $identifier,
        ?int   $maxRequests = null,
        ?int   $windowSeconds = null
    ): int
    {
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['default'];

        $maxRequests = $maxRequests ?? $limits['requests'];
        $windowSeconds = $windowSeconds ?? $limits['window'];

        $key = $this->getKey($action, $identifier);
        $now = time();
        $windowStart = $now - $windowSeconds;

        $count = $this->countRequests($key, $windowStart);

        return max(0, $maxRequests - $count);
    }

    /**
     * Get seconds until rate limit resets
     *
     * @param string $action Action being performed
     * @param string $identifier Unique identifier
     * @param int|null $windowSeconds Time window in seconds
     * @return int Seconds until reset
     */
    public function retryAfter(
        string $action,
        string $identifier,
        ?int   $windowSeconds = null
    ): int
    {
        $limits = self::DEFAULT_LIMITS[$action] ?? self::DEFAULT_LIMITS['default'];
        $windowSeconds = $windowSeconds ?? $limits['window'];

        $key = $this->getKey($action, $identifier);

        if (!isset(self::$storage[$key]) || empty(self::$storage[$key])) {
            return 0;
        }

        // Get oldest timestamp in current window
        $oldestTimestamp = min(array_keys(self::$storage[$key]));
        $now = time();

        $resetTime = $oldestTimestamp + $windowSeconds;

        return max(0, $resetTime - $now);
    }

    /**
     * Clear rate limit for specific action/identifier
     *
     * @param string $action Action being performed
     * @param string $identifier Unique identifier
     * @return void
     */
    public function clear(string $action, string $identifier): void
    {
        $key = $this->getKey($action, $identifier);
        unset(self::$storage[$key]);
    }

    /**
     * Rate limit based on Request object (uses client IP)
     *
     * @param Request $request Current request
     * @param string $action Action being performed
     * @param int|null $maxRequests Max requests allowed
     * @param int|null $windowSeconds Time window in seconds
     * @return bool True if request is allowed
     */
    public function attemptFromRequest(
        Request $request,
        string  $action,
        ?int    $maxRequests = null,
        ?int    $windowSeconds = null
    ): bool
    {
        $identifier = $request->getClientIp() ?? 'unknown';
        return $this->attempt($action, $identifier, $maxRequests, $windowSeconds);
    }

    /**
     * Generate storage key
     *
     * @param string $action Action name
     * @param string $identifier Unique identifier
     * @return string Storage key
     */
    private function getKey(string $action, string $identifier): string
    {
        return sprintf('rate_limit:%s:%s', $action, $identifier);
    }

    /**
     * Record an attempt
     *
     * @param string $key Storage key
     * @param int $timestamp Current timestamp
     * @return void
     */
    private function record(string $key, int $timestamp): void
    {
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [];
        }

        // Use timestamp as key and increment count for deduplication
        if (!isset(self::$storage[$key][$timestamp])) {
            self::$storage[$key][$timestamp] = 0;
        }

        self::$storage[$key][$timestamp]++;
    }

    /**
     * Count requests in current window
     *
     * @param string $key Storage key
     * @param int $windowStart Window start timestamp
     * @return int Number of requests
     */
    private function countRequests(string $key, int $windowStart): int
    {
        if (!isset(self::$storage[$key])) {
            return 0;
        }

        $count = 0;

        foreach (self::$storage[$key] as $timestamp => $requestCount) {
            if ($timestamp >= $windowStart) {
                $count += $requestCount;
            }
        }

        return $count;
    }

    /**
     * Clean up old entries outside the window
     *
     * @param string $key Storage key
     * @param int $windowStart Window start timestamp
     * @return void
     */
    private function cleanup(string $key, int $windowStart): void
    {
        if (!isset(self::$storage[$key])) {
            return;
        }

        foreach (self::$storage[$key] as $timestamp => $count) {
            if ($timestamp < $windowStart) {
                unset(self::$storage[$key][$timestamp]);
            }
        }

        // Remove key if empty
        if (empty(self::$storage[$key])) {
            unset(self::$storage[$key]);
        }
    }

    /**
     * Clear all rate limit data (for testing)
     *
     * @return void
     */
    public function clearAll(): void
    {
        self::$storage = [];
    }
}
