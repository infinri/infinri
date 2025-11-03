<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Url;

use Infinri\Core\App\Router;

/**
 * Generates URLs from route names with parameters and query strings
 */
class Builder
{
    /**
     * Base URL
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * Use secure (https) URLs
     *
     * @var bool
     */
    private bool $secure;

    /**
     * Constructor
     *
     * @param string|null $baseUrl Base URL (null = auto-detect)
     * @param bool $secure Use HTTPS
     */
    public function __construct(?string $baseUrl = null, bool $secure = false)
    {
        $this->baseUrl = $baseUrl ?? $this->detectBaseUrl();
        $this->secure = $secure;
    }

    /**
     * Build URL from route name
     *
     * @param string $routeName Route name or path
     * @param array<string, mixed> $params Route parameters
     * @param array<string, mixed> $query Query string parameters
     * @return string Generated URL
     */
    public function build(string $routeName, array $params = [], array $query = []): string
    {
        // If it's already a full URL, return as-is
        if ($this->isAbsoluteUrl($routeName)) {
            return $routeName;
        }

        // Start with base URL
        $url = $this->baseUrl;

        // Build path from route name or use as-is if it starts with /
        if (str_starts_with($routeName, '/')) {
            $path = $routeName;
        } else {
            $path = '/' . ltrim($routeName, '/');
        }

        // Replace route parameters
        foreach ($params as $key => $value) {
            $path = str_replace(':' . $key, (string)$value, $path);
        }

        $url .= $path;

        // Add query string
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * Build URL with route name lookup (if router available)
     *
     * @param string $routeName Route name
     * @param array<string, mixed> $params Route parameters
     * @param array<string, mixed> $query Query string parameters
     * @return string Generated URL
     */
    public function route(string $routeName, array $params = [], array $query = []): string
    {
        // For now, treat route name as path
        // Future: lookup route definition from router
        return $this->build($routeName, $params, $query);
    }

    /**
     * Build absolute URL
     *
     * @param string $path Path or route name
     * @param array<string, mixed> $params Route parameters
     * @param array<string, mixed> $query Query string parameters
     * @return string Absolute URL
     */
    public function absolute(string $path, array $params = [], array $query = []): string
    {
        $url = $this->build($path, $params, $query);

        // If already absolute, return (but respect secure flag)
        if ($this->isAbsoluteUrl($url)) {
            if ($this->secure && str_starts_with($url, 'http://')) {
                return str_replace('http://', 'https://', $url);
            }
            return $url;
        }

        // Prepend scheme and host
        $scheme = $this->secure ? 'https' : 'http';
        return $scheme . '://' . $this->getHost() . $url;
    }

    /**
     * Build secure (HTTPS) URL
     *
     * @param string $path Path or route name
     * @param array<string, mixed> $params Route parameters
     * @param array<string, mixed> $query Query string parameters
     * @return string Secure URL
     */
    public function secure(string $path, array $params = [], array $query = []): string
    {
        $originalSecure = $this->secure;
        $this->secure = true;

        $url = $this->absolute($path, $params, $query);

        $this->secure = $originalSecure;

        return $url;
    }

    /**
     * Get current URL
     *
     * @return string Current URL
     */
    public function current(): string
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $this->baseUrl . $_SERVER['REQUEST_URI'];
        }

        return $this->baseUrl;
    }

    /**
     * Get previous URL (from referer)
     *
     * @param string|null $default Default URL if no referer
     * @return string Previous URL
     */
    public function previous(?string $default = null): string
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }

        return $default ?? $this->baseUrl;
    }

    /**
     * Check if URL is absolute
     *
     * @param string $url URL to check
     * @return bool True if absolute
     */
    private function isAbsoluteUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//');
    }

    /**
     * Detect base URL from request
     *
     * @return string Base URL
     */
    private function detectBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return '';
        }

        return '';
    }

    /**
     * Get host from request
     *
     * @return string Host
     */
    private function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Set base URL
     *
     * @param string $baseUrl Base URL
     * @return void
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get base URL
     *
     * @return string Base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Set secure flag
     *
     * @param bool $secure Use HTTPS
     * @return void
     */
    public function setSecure(bool $secure): void
    {
        $this->secure = $secure;
    }

    /**
     * Check if using secure URLs
     *
     * @return bool True if secure
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }
}
