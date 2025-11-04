<?php

declare(strict_types=1);

namespace Infinri\Core\App\Middleware;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Adds security-related HTTP headers to all responses to protect against:
 * - XSS attacks
 * - Clickjacking
 * - MIME-sniffing
 * - Man-in-the-middle attacks
 *
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeadersMiddleware
{
    /**
     * Apply security headers to the response.
     */
    public function handle(Request $request, Response $response): Response
    {
        // Prevent clickjacking attacks
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-sniffing attacks
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection in browsers
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Referrer policy - don't send full URL to external sites
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature Policy)
        $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy (CSP)
        $response->setHeader('Content-Security-Policy', $this->getContentSecurityPolicy());

        // HTTP Strict Transport Security (HSTS) - Only for HTTPS
        if ($this->isHttps($request)) {
            // max-age=31536000 = 1 year, includeSubDomains, preload
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    /**
     * Build Content Security Policy directives
     * CSP helps prevent XSS, clickjacking, and other code injection attacks.
     */
    private function getContentSecurityPolicy(): string
    {
        // Generate cryptographically secure nonce for this request
        $nonce = base64_encode(random_bytes(16));

        // Store nonce in global for templates to use
        $_SERVER['CSP_NONCE'] = $nonce;

        $directives = [
            // Default fallback for any resource type not covered by specific directives
            "default-src 'self'",

            // Scripts: ONLY allow same-origin scripts and nonce-tagged inline scripts
            "script-src 'self' 'nonce-{$nonce}'",

            // Styles: ONLY allow same-origin styles and nonce-tagged inline styles
            "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com",

            // Images: allow from same origin, data URIs (base64 images), and blob (image picker)
            "img-src 'self' data: blob:",

            // Fonts: from same origin, data URIs, and common font CDNs
            "font-src 'self' data: https://fonts.gstatic.com",

            // AJAX/fetch requests: same origin only
            "connect-src 'self'",

            // Frames: only from same origin (media picker iframe)
            "frame-src 'self'",

            // Form submissions: only to same origin
            "form-action 'self'",

            // Base tag restrictions
            "base-uri 'self'",

            // Embedding restrictions (controls where this site can be embedded)
            "frame-ancestors 'self'",

            // Report CSP violations to this endpoint (for monitoring)
            'report-uri /csp-report',
        ];

        return implode('; ', $directives);
    }

    /**
     * Check if the request is over HTTPS.
     */
    private function isHttps(Request $request): bool
    {
        // Check standard HTTPS indicators
        if (! empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) {
            return true;
        }

        // Check for load balancer/proxy headers
        if (! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL']) {
            return true;
        }

        // Check server port
        if (! empty($_SERVER['SERVER_PORT']) && 443 === (int) $_SERVER['SERVER_PORT']) {
            return true;
        }

        return false;
    }
}
