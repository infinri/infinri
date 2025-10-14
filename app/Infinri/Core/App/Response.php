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
     * Send redirect response
     *
     * @param string $url
     * @param int $code
     * @return $this
     */
    public function setRedirect(string $url, int $code = 302): self
    {
        $this->setStatusCode($code);
        $this->setHeader('Location', $url);
        return $this;
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
     * @return void
     */
    public function send(): void
    {
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
}
