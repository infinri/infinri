<?php
declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * Wrapper for HTTP request data (GET, POST, SERVER, etc.)
 */
class Request
{
    /**
     * @var array<string, mixed> GET parameters
     */
    private array $query;

    /**
     * @var array<string, mixed> POST parameters
     */
    private array $post;

    /**
     * @var array<string, mixed> Server/environment variables
     */
    private array $server;

    /**
     * @var array<string, mixed> Cookies
     */
    private array $cookies;

    /**
     * @var array<string, mixed> Route parameters
     */
    private array $params = [];

    public function __construct(
        ?array $query = null,
        ?array $post = null,
        ?array $server = null,
        ?array $cookies = null
    ) {
        $this->query = $query ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
        $this->cookies = $cookies ?? $_COOKIE;
    }

    /**
     * Create from globals
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_COOKIE);
    }

    /**
     * Get request method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request method is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if request method is GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Get request URI path
     *
     * @return string
     */
    public function getPathInfo(): string
    {
        $requestUri = $this->server['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        return $requestUri;
    }

    /**
     * Get request URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->getPathInfo();
    }

    /**
     * Get request path (alias for getUri)
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->getUri();
    }

    /**
     * Get query parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get parameter (checks GET, then POST, then route params)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $this->post[$key] ?? $this->params[$key] ?? $default;
    }

    /**
     * Get parameter as string with automatic trimming
     *
     * @param string $key Parameter name
     * @param string $default Default value if not found
     * @return string Trimmed string value
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->getParam($key, $default);
        return is_string($value) ? trim($value) : (string)$default;
    }

    /**
     * Get parameter as integer with validation
     *
     * @param string $key Parameter name
     * @param int $default Default value if not found or invalid
     * @return int Validated integer
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getParam($key, $default);
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Get parameter as boolean
     *
     * Recognizes: true/false, 1/0, "true"/"false", "yes"/"no", "on"/"off"
     *
     * @param string $key Parameter name
     * @param bool $default Default value if not found
     * @return bool Validated boolean
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getParam($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Get parameter as array
     *
     * @param string $key Parameter name
     * @param array $default Default value if not found
     * @return array Validated array
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->getParam($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get parameter as validated email address
     *
     * @param string $key Parameter name
     * @param string|null $default Default value if not found or invalid
     * @return string|null Validated email or default
     */
    public function getEmail(string $key, ?string $default = null): ?string
    {
        $value = $this->getParam($key, $default);

        if ($value === null || $value === '') {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_EMAIL);
        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Get parameter as validated URL
     *
     * @param string $key Parameter name
     * @param string|null $default Default value if not found or invalid
     * @return string|null Validated URL or default
     */
    public function getUrl(string $key, ?string $default = null): ?string
    {
        $value = $this->getParam($key, $default);

        if ($value === null || $value === '') {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_URL);
        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Get parameter as float/decimal
     *
     * @param string $key Parameter name
     * @param float $default Default value if not found or invalid
     * @return float Validated float
     */
    public function getFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getParam($key, $default);
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);
        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Set route parameter
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParam(string $key, mixed $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Get all route parameters
     *
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get server parameter
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getServer(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get cookie
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Get client IP address
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        // Get direct connection IP (always available)
        $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;

        if (!$remoteAddr) {
            return null;
        }

        // Get trusted proxy IPs from environment configuration
        $trustedProxies = $this->getTrustedProxies();

        // Only trust proxy headers if request comes from a trusted proxy
        if ($this->isFromTrustedProxy($remoteAddr, $trustedProxies)) {
            // Trust X-Forwarded-For header from proxy
            if (isset($this->server['HTTP_X_FORWARDED_FOR'])) {
                $forwardedIps = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
                // Return the FIRST IP in the chain (original client)
                $clientIp = trim($forwardedIps[0]);
                if ($this->isValidIp($clientIp)) {
                    return $clientIp;
                }
            }

            // Fallback to X-Real-IP if X-Forwarded-For not present
            if (isset($this->server['HTTP_X_REAL_IP'])) {
                $clientIp = trim($this->server['HTTP_X_REAL_IP']);
                if ($this->isValidIp($clientIp)) {
                    return $clientIp;
                }
            }
        }

        // Return direct connection IP (not behind proxy or untrusted proxy)
        return $remoteAddr;
    }

    /**
     * Get trusted proxy IPs from environment configuration
     *
     * @return array
     */
    private function getTrustedProxies(): array
    {
        $proxies = $_ENV['TRUSTED_PROXIES'] ?? getenv('TRUSTED_PROXIES') ?: '';

        if (empty($proxies)) {
            // Default: trust localhost only
            return ['127.0.0.1', '::1'];
        }

        return array_map('trim', explode(',', $proxies));
    }

    /**
     * Check if request is from a trusted proxy
     *
     * @param string $remoteAddr
     * @param array $trustedProxies
     * @return bool
     */
    private function isFromTrustedProxy(string $remoteAddr, array $trustedProxies): bool
    {
        foreach ($trustedProxies as $proxy) {
            // Support CIDR notation (e.g., 10.0.0.0/8)
            if (str_contains($proxy, '/')) {
                if ($this->ipInRange($remoteAddr, $proxy)) {
                    return true;
                }
            } else {
                // Exact IP match
                if ($remoteAddr === $proxy) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Validate IP address format
     *
     * @param string $ip
     * @return bool
     */
    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            || filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Get HTTP host
     *
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->server['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Check if request is secure (HTTPS)
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? 80) == 443;
    }

    /**
     * Get user agent string
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get request scheme (http or https)
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Get host name
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Get server port
     *
     * @return int
     */
    public function getPort(): int
    {
        return (int)($this->server['SERVER_PORT'] ?? 80);
    }
}
