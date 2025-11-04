<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

use Infinri\Core\App\Response;

/**
 * Standardized JSON responses for API endpoints.
 */
class JsonResponse
{
    /**
     * Create success JSON response.
     *
     * @param array<string, mixed> $data Additional data to include
     */
    public static function success(array $data = []): Response
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        $payload = array_merge(['success' => true], $data);
        $json = json_encode($payload);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode JSON response');
        }
        $response->setBody($json);

        return $response;
    }

    /**
     * Create error JSON response.
     *
     * @param string               $message        Error message
     * @param int                  $httpCode       HTTP status code (default 500)
     * @param array<string, mixed> $additionalData Additional error data
     */
    public static function error(string $message, int $httpCode = 500, array $additionalData = []): Response
    {
        $response = new Response();
        $response->setStatusCode($httpCode);
        $response->setHeader('Content-Type', 'application/json');

        $payload = array_merge([
            'success' => false,
            'error' => $message,
        ], $additionalData);

        $json = json_encode($payload);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode JSON response');
        }
        $response->setBody($json);

        return $response;
    }

    /**
     * Create forbidden (403) JSON response.
     *
     * @param string $message Error message (default: 'Forbidden')
     */
    public static function forbidden(string $message = 'Forbidden'): Response
    {
        return self::error($message, 403);
    }

    /**
     * Create unauthorized (401) JSON response.
     *
     * @param string $message Error message (default: 'Unauthorized')
     */
    public static function unauthorized(string $message = 'Unauthorized'): Response
    {
        return self::error($message, 401);
    }

    /**
     * Create bad request (400) JSON response.
     *
     * @param string $message Error message
     */
    public static function badRequest(string $message): Response
    {
        return self::error($message, 400);
    }

    /**
     * Create not found (404) JSON response.
     *
     * @param string $message Error message (default: 'Not Found')
     */
    public static function notFound(string $message = 'Not Found'): Response
    {
        return self::error($message, 404);
    }

    /**
     * Create CSRF token error response.
     */
    public static function csrfError(): Response
    {
        return self::forbidden('Invalid CSRF token');
    }
}
