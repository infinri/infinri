<?php

declare(strict_types=1);

namespace Infinri\Core\App\Middleware;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Core\Security\CsrfTokenManager;

/**
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE, PATCH).
 */
class CsrfProtectionMiddleware
{
    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {
    }

    /**
     * Handle request and validate CSRF token if required.
     */
    public function handle(Request $request, Response $response): Response
    {
        // Only check POST, PUT, DELETE, PATCH requests
        $method = $request->getMethod();
        if (! \in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return $response;
        }

        // Skip CSRF for API endpoints (they should use bearer tokens)
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/api/')) {
            return $response;
        }

        // Get CSRF token from request
        $token = $request->getPost('_csrf_token', '');
        $tokenId = $request->getPost('_csrf_token_id', 'default');

        // Validate token
        if (! $this->csrfManager->validateToken($tokenId, $token)) {
            Logger::warning('CSRF token validation failed', [
                'path' => $path,
                'method' => $method,
                'ip' => $request->getClientIp(),
                'token_id' => $tokenId,
            ]);

            $response->setStatusCode(403);
            $response->setBody(
                '403 Forbidden: CSRF token validation failed. Please refresh the page and try again.'
            );

            return $response;
        }

        // Token valid, continue
        return $response;
    }
}
