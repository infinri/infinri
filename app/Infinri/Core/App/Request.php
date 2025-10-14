<?php
declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * HTTP Request
 * 
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
        $keys = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (isset($this->server[$key])) {
                $ip = $this->server[$key];
                // Get first IP if comma-separated
                if (str_contains($ip, ',')) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return null;
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
}
