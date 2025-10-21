<?php

declare(strict_types=1);

namespace Infinri\Core\App\Middleware;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Security Headers Middleware
 * 
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
     * Apply security headers to the response
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
     * 
     * CSP helps prevent XSS, clickjacking, and other code injection attacks
     */
    private function getContentSecurityPolicy(): string
    {
        $directives = [
            // Default fallback for any resource type not covered by specific directives
            "default-src 'self'",
            
            // Scripts: allow inline scripts and eval (needed for some admin functionality)
            // In production, consider removing 'unsafe-inline' and using nonces
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            
            // Styles: allow inline styles (used extensively in current templates)
            "style-src 'self' 'unsafe-inline'",
            
            // Images: allow from same origin, data URIs (base64 images), and blob (image picker)
            "img-src 'self' data: blob:",
            
            // Fonts: from same origin only
            "font-src 'self'",
            
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
        ];
        
        return implode('; ', $directives);
    }
    
    /**
     * Check if the request is over HTTPS
     */
    private function isHttps(Request $request): bool
    {
        // Check standard HTTPS indicators
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        
        // Check for load balancer/proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        // Check server port
        if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        
        return false;
    }
}
