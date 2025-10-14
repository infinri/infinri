<?php

declare(strict_types=1);

use Infinri\Core\App\Request;

/**
 * Full Application Integration Tests
 * 
 * These tests initialize the entire application and test real HTTP requests
 */

beforeEach(function () {
    // Bootstrap the application
    require_once __DIR__ . '/../../app/bootstrap.php';
    $this->frontController = initApplication();
});

describe('Application Integration', function () {
    
    it('can handle homepage request', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(200);
        expect($response->getBody())->toContain('Welcome to Infinri Framework');
        expect($response->getBody())->toContain('193 tests passing');
    });
    
    it('can handle about page request', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/about', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(200);
        expect($response->getBody())->toContain('About Infinri Framework');
        expect($response->getBody())->toContain('PHP-DI');
    });
    
    it('can handle product view with parameter', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/product/42', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        // Route matched and controller executed successfully
        expect($response->getStatusCode())->toBe(200);
        expect($response->getBody())->toContain('Product View');
        expect($response->getBody())->toContain('demonstrates URL parameter extraction');
        
        // Note: Parameter extraction is tested separately in unit tests
        // This integration test focuses on end-to-end request handling
    });
    
    it('can handle API JSON response', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/api/test', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeader('Content-Type'))->toBe('application/json');
        
        $data = json_decode($response->getBody(), true);
        expect($data)->toHaveKey('status');
        expect($data['status'])->toBe('success');
        expect($data['framework'])->toBe('Infinri');
    });
    
    it('returns 404 for non-existent route', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(404);
        expect($response->getBody())->toContain('404');
    });
    
    it('filters routes by HTTP method', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'POST']
        );
        
        $response = $this->frontController->dispatch($request);
        
        // Homepage only accepts GET, so POST should fail
        expect($response->getStatusCode())->toBe(404);
    });
    
    it('renders template blocks correctly', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        // Check that layout is rendered with proper structure
        expect($response->getBody())->toContain('<html');
        expect($response->getBody())->toContain('<body');
        expect($response->getBody())->toContain('<header');
        expect($response->getBody())->toContain('<footer');
        expect($response->getBody())->toContain('page-home');
    });
    
    it('loads configuration and modules correctly', function () {
        $request = new Request(
            [],
            [],
            ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET']
        );
        
        $response = $this->frontController->dispatch($request);
        
        // If modules and config weren't loaded, this would fail
        expect($response->getStatusCode())->toBe(200);
    });
    
});
