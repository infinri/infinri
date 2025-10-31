<?php

declare(strict_types=1);

use Infinri\Core\App\Middleware\SecurityHeadersMiddleware;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

describe('SecurityHeadersMiddleware', function () {
    beforeEach(function () {
        $this->middleware = new SecurityHeadersMiddleware();
        $this->request = new Request();
        $this->response = new Response();
    });

    it('adds X-Frame-Options header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('X-Frame-Options'))->toBe('SAMEORIGIN');
    });

    it('adds X-Content-Type-Options header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('X-Content-Type-Options'))->toBe('nosniff');
    });

    it('adds X-XSS-Protection header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('X-XSS-Protection'))->toBe('1; mode=block');
    });

    it('adds Referrer-Policy header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });

    it('adds Permissions-Policy header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('Permissions-Policy'))->toBe('geolocation=(), microphone=(), camera=()');
    });

    it('adds Content-Security-Policy header', function () {
        $response = $this->middleware->handle($this->request, $this->response);
        
        $csp = $response->getHeader('Content-Security-Policy');
        expect($csp)->toContain("default-src 'self'");
        expect($csp)->toContain("script-src 'self'");
        expect($csp)->toContain("style-src 'self'");
        expect($csp)->toContain("https://fonts.googleapis.com");
        expect($csp)->toContain("https://fonts.gstatic.com");
    });

    it('adds HSTS header for HTTPS requests', function () {
        // Simulate HTTPS request
        $_SERVER['HTTPS'] = 'on';
        
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('Strict-Transport-Security'))
            ->toBe('max-age=31536000; includeSubDomains; preload');
        
        unset($_SERVER['HTTPS']);
    });

    it('does not add HSTS header for HTTP requests', function () {
        // Ensure HTTP request
        unset($_SERVER['HTTPS']);
        
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('Strict-Transport-Security'))->toBeNull();
    });

    it('detects HTTPS from X-Forwarded-Proto header', function () {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getHeader('Strict-Transport-Security'))->not->toBeNull();
        
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
    });

    it('preserves existing response body and status', function () {
        $this->response->setBody('Test content');
        $this->response->setHeader('Content-Type', 'text/html');
        
        $response = $this->middleware->handle($this->request, $this->response);
        
        expect($response->getBody())->toBe('Test content');
        expect($response->getHeader('Content-Type'))->toBe('text/html');
    });
});
