<?php
declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * HTTP Response
 * 
 * Wrapper for HTTP response (headers, body, status code)
 */
class Response
{
    /**
     * @var string Response body
     */
    private string $body = '';

    /**
     * @var int HTTP status code
     */
    private int $statusCode = 200;

    /**
     * @var array<string, string> HTTP headers
     */
    private array $headers = [];

    /**
     * @var bool Whether headers have been sent
     */
    private bool $headersSent = false;

    /**
     * Set response body
     *
     * @param string $body
     * @return $this
     */
    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Append to response body
     *
     * @param string $content
     * @return $this
     */
    public function appendBody(string $content): self
    {
        $this->body .= $content;
        return $this;
    }

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set HTTP status code
     *
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP header
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get HTTP header
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set content type header
     *
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType): self
    {
        return $this->setHeader('Content-Type', $contentType);
    }
    
    /**
     * Set security headers
     *
     * @param bool $strict If true, sets stricter CSP and security policies
     * @return $this
     */
    public function setSecurityHeaders(bool $strict = false): self
    {
        // Prevent clickjacking attacks
        $this->setHeader('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent MIME type sniffing
        $this->setHeader('X-Content-Type-Options', 'nosniff');
        
        // Enable XSS protection in older browsers
        $this->setHeader('X-XSS-Protection', '1; mode=block');
        
        // Referrer policy - don't send full URL to external sites
        $this->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        if ($strict) {
            // Strict CSP - only same origin
            $this->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'; frame-ancestors 'self'");
        } else {
            // Lenient CSP - allows inline scripts/styles (for development)
            $this->setHeader('Content-Security-Policy', "default-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; frame-ancestors 'self'");
        }
        
        // Permissions policy - disable unnecessary features
        $this->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        return $this;
    }

    /**
     * Send redirect response
     *
     * @param string $url
     * @param int $code
     * @return $this
     * @throws \InvalidArgumentException If URL is not safe for redirect
     */
    public function setRedirect(string $url, int $code = 302): self
    {
        // Validate URL to prevent open redirect attacks
        if (!$this->isValidRedirectUrl($url)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid redirect URL: %s. Only relative URLs or same-host URLs are allowed.', $url)
            );
        }
        
        $this->setStatusCode($code);
        $this->setHeader('Location', $url);
        return $this;
    }
    
    /**
     * Convenience method for redirect (alias of setRedirect)
     *
     * @param string $url
     * @param int $code
     * @return $this
     * @throws \InvalidArgumentException If URL is not safe for redirect
     */
    public function redirect(string $url, int $code = 302): self
    {
        return $this->setRedirect($url, $code);
    }

    /**
     * Set JSON response
     *
     * @param mixed $data
     * @return $this
     */
    public function setJson(mixed $data): self
    {
        $this->setContentType('application/json');
        $this->setBody(json_encode($data, JSON_THROW_ON_ERROR));
        return $this;
    }

    /**
     * Send headers
     *
     * @return $this
     */
    public function sendHeaders(): self
    {
        if ($this->headersSent || headers_sent()) {
            return $this;
        }

        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        $this->headersSent = true;
        return $this;
    }

    /**
     * Send response (headers + body)
     *
     * @param bool $withSecurityHeaders If true, automatically adds security headers
     * @return void
     */
    public function send(bool $withSecurityHeaders = true): void
    {
        // Automatically add security headers unless disabled
        if ($withSecurityHeaders && !isset($this->headers['X-Frame-Options'])) {
            $this->setSecurityHeaders();
        }
        
        $this->sendHeaders();
        echo $this->body;
    }

    /**
     * Check if headers have been sent
     *
     * @return bool
     */
    public function isHeadersSent(): bool
    {
        return $this->headersSent || headers_sent();
    }

    /**
     * Set 404 Not Found response
     *
     * @return $this
     */
    public function setNotFound(): self
    {
        return $this->setStatusCode(404);
    }

    /**
     * Set 500 Internal Server Error response
     *
     * @return $this
     */
    public function setServerError(): self
    {
        return $this->setStatusCode(500);
    }

    /**
     * Set 403 Forbidden response
     *
     * @return $this
     */
    public function setForbidden(): self
    {
        return $this->setStatusCode(403);
    }
    
    /**
     * Validate redirect URL to prevent open redirect attacks
     *
     * @param string $url
     * @return bool
     */
    private function isValidRedirectUrl(string $url): bool
    {
        // Allow relative URLs (start with /)
        if (str_starts_with($url, '/')) {
            return true;
        }
        
        // Parse the URL
        $parsed = parse_url($url);
        
        // If parsing fails, reject
        if ($parsed === false) {
            return false;
        }
        
        // If no host, it's a relative URL (e.g., "path/to/page")
        if (!isset($parsed['host'])) {
            return true;
        }
        
        // Get current host from server variables
        $currentHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        
        // Strip port from current host if present
        $currentHost = explode(':', $currentHost)[0];
        
        // Allow same-host redirects
        if (strcasecmp($parsed['host'], $currentHost) === 0) {
            return true;
        }
        
        // Reject external redirects
        return false;
    }
}
