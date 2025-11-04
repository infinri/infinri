<?php

declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * Provides centralized, secure session management with:
 * - Automatic session start with security settings
 * - Type-safe get/set operations
 * - Flash messages support
 * - Session timeout detection
 * - Activity tracking
 * - Session regeneration for security
 */
class Session
{
    private bool $started = false;

    /**
     * Default session timeout (1 hour).
     */
    private const DEFAULT_TIMEOUT = 3600;

    /**
     * Start session with secure configuration.
     */
    public function start(): void
    {
        if ($this->started || \PHP_SESSION_ACTIVE === session_status()) {
            $this->started = true;

            return;
        }

        session_start([
            'cookie_httponly' => true,      // Prevent JavaScript access
            'cookie_secure' => true,         // HTTPS only (Phase 1.5)
            'cookie_samesite' => 'Strict',   // CSRF protection (Phase 1.5)
            'use_strict_mode' => true,       // Reject uninitialized session IDs
            'use_only_cookies' => true,      // Don't accept session ID in URL
            'cookie_lifetime' => 0,          // Session cookie (expires on browser close)
        ]);

        $this->started = true;

        // Track activity for timeout detection
        if (! $this->has('_initialized')) {
            $this->set('_initialized', true);
            $this->updateActivity();
        }
    }

    /**
     * Get value from session.
     *
     * @param string $key     Session key
     * @param mixed  $default Default value if key doesn't exist
     *
     * @return mixed Session value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set value in session.
     *
     * @param string $key   Session key
     * @param mixed  $value Value to store
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists.
     *
     * @param string $key Session key
     *
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();

        return isset($_SESSION[$key]);
    }

    /**
     * Remove value from session.
     *
     * @param string $key Session key to remove
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data.
     */
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    /**
     * Regenerate session ID for security
     * Call this after login to prevent session fixation attacks.
     *
     * @param bool $deleteOldSession Whether to delete old session file
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        $this->ensureStarted();
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Destroy session completely.
     */
    public function destroy(): void
    {
        $this->ensureStarted();

        $_SESSION = [];

        // Delete session cookie
        if (\ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $sessionName = session_name();
            if (false !== $sessionName) {
                setcookie(
                    $sessionName,
                    '',
                    [
                        'expires' => time() - 42000,
                        'path' => $params['path'],
                        'domain' => $params['domain'],
                        'secure' => $params['secure'],
                        'httponly' => $params['httponly'],
                        'samesite' => $params['samesite'],
                    ]
                );
            }
        }

        session_destroy();
        $this->started = false;
    }

    /**
     * Set flash message (available for next request only).
     *
     * @param string $key   Flash message key
     * @param mixed  $value Flash message value
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flash message and remove it.
     *
     * @param string $key     Flash message key
     * @param mixed  $default Default value if not found
     *
     * @return mixed Flash message value or default
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    /**
     * Check if flash message exists.
     *
     * @param string $key Flash message key
     *
     * @return bool True if flash message exists
     */
    public function hasFlash(string $key): bool
    {
        $this->ensureStarted();

        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Set success flash message.
     *
     * @param string $message Success message
     */
    public function addSuccess(string $message): void
    {
        $this->flash('success', $message);
    }

    /**
     * Set error flash message.
     *
     * @param string $message Error message
     */
    public function addError(string $message): void
    {
        $this->flash('error', $message);
    }

    /**
     * Set warning flash message.
     *
     * @param string $message Warning message
     */
    public function addWarning(string $message): void
    {
        $this->flash('warning', $message);
    }

    /**
     * Set info flash message.
     *
     * @param string $message Info message
     */
    public function addInfo(string $message): void
    {
        $this->flash('info', $message);
    }

    /**
     * Check if session has expired due to inactivity.
     *
     * @param int $maxLifetime Maximum session lifetime in seconds
     *
     * @return bool True if session has expired
     */
    public function isExpired(int $maxLifetime = self::DEFAULT_TIMEOUT): bool
    {
        $this->ensureStarted();
        $lastActivity = $this->get('_last_activity', time());

        return (time() - $lastActivity) > $maxLifetime;
    }

    /**
     * Update last activity timestamp
     * Call this on each request to prevent timeout.
     */
    public function updateActivity(): void
    {
        $this->set('_last_activity', time());
    }

    /**
     * Get session fingerprint for additional security
     * Creates a hash of user agent + IP to detect session hijacking.
     *
     * @param Request $request Current request
     *
     * @return string Session fingerprint
     */
    public function getFingerprint(Request $request): string
    {
        $userAgent = $request->getUserAgent();
        $ip = $request->getClientIp() ?? '';

        return hash('sha256', $userAgent . $ip);
    }

    /**
     * Verify session fingerprint matches current request.
     *
     * @param Request $request Current request
     *
     * @return bool True if fingerprint matches
     */
    public function verifyFingerprint(Request $request): bool
    {
        $stored = $this->get('_fingerprint');

        if (! $stored) {
            // First time - store fingerprint
            $this->set('_fingerprint', $this->getFingerprint($request));

            return true;
        }

        return hash_equals($stored, $this->getFingerprint($request));
    }

    /**
     * Get session ID.
     *
     * @return string Session ID
     */
    public function getId(): string
    {
        $this->ensureStarted();
        $id = session_id();
        if (false === $id) {
            throw new \RuntimeException('Failed to get session ID');
        }

        return $id;
    }

    /**
     * Get session name.
     *
     * @return string Session name
     */
    public function getName(): string
    {
        $name = session_name();
        if (false === $name) {
            throw new \RuntimeException('Failed to get session name');
        }

        return $name;
    }

    /**
     * Check if session is started.
     *
     * @return bool True if session is active
     */
    public function isStarted(): bool
    {
        return $this->started && \PHP_SESSION_ACTIVE === session_status();
    }

    /**
     * Ensure session is started.
     */
    private function ensureStarted(): void
    {
        if (! $this->isStarted()) {
            $this->start();
        }
    }

    /**
     * Get all session data (for debugging).
     *
     * @return array All session data
     */
    public function all(): array
    {
        $this->ensureStarted();

        return $_SESSION;
    }
}
