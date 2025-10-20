<?php

declare(strict_types=1);

use Infinri\Core\App\FastRouter;

describe('FastRouter', function () {
    
    beforeEach(function () {
        $this->router = new FastRouter();
    });
    
    it('can register and match simple routes', function () {
        $this->router->addRoute('home', '/', 'HomeController', 'execute', ['GET']);
        $this->router->addRoute('about', '/about', 'AboutController', 'execute', ['GET']);
        
        $match = $this->router->match('/', 'GET');
        
        expect($match)->toBeArray();
        expect($match['name'])->toBe('home');
        expect($match['controller'])->toBe('HomeController');
        expect($match['action'])->toBe('execute');
    });
    
    it('can match routes with parameters', function () {
        $this->router->addRoute('product', '/product/:id', 'ProductController', 'view', ['GET']);
        
        $match = $this->router->match('/product/123', 'GET');
        
        expect($match)->not->toBeNull();
        expect($match['controller'])->toBe('ProductController');
        expect($match['params']['id'])->toBe('123');
    });
    
    it('can match routes with multiple parameters', function () {
        $this->router->addRoute('user_post', '/user/:userId/post/:postId', 'PostController', 'view', ['GET']);
        
        $match = $this->router->match('/user/42/post/100', 'GET');
        
        expect($match)->not->toBeNull();
        expect($match['params']['userId'])->toBe('42');
        expect($match['params']['postId'])->toBe('100');
    });
    
    it('returns null for non-matching routes', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        
        $match = $this->router->match('/nonexistent', 'GET');
        
        expect($match)->toBeNull();
    });
    
    it('respects HTTP method restrictions', function () {
        $this->router->addRoute('create', '/create', 'CreateController', 'execute', ['POST']);
        
        $matchPost = $this->router->match('/create', 'POST');
        $matchGet = $this->router->match('/create', 'GET');
        
        expect($matchPost)->not->toBeNull();
        expect($matchGet)->toBeNull();
    });
    
    it('can generate URLs from route names', function () {
        $this->router->addRoute('product', '/product/:id', 'ProductController');
        
        $url = $this->router->generate('product', ['id' => '456']);
        
        expect($url)->toBe('/product/456');
    });
    
    it('returns all registered routes', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        $this->router->addRoute('about', '/about', 'AboutController');
        
        $routes = $this->router->getRoutes();
        
        expect($routes)->toHaveCount(2);
        expect($routes)->toHaveKey('home');
        expect($routes)->toHaveKey('about');
    });
    
    it('handles routes with same path but different methods', function () {
        $this->router->addRoute('get_form', '/form', 'FormController', 'show', ['GET']);
        $this->router->addRoute('post_form', '/form', 'FormController', 'submit', ['POST']);
        
        $matchGet = $this->router->match('/form', 'GET');
        $matchPost = $this->router->match('/form', 'POST');
        
        expect($matchGet['action'])->toBe('show');
        expect($matchPost['action'])->toBe('submit');
    });
    
    it('handles admin routes with prefix', function () {
        $this->router->addRoute('admin_dashboard', '/admin/dashboard', 'Admin\\DashboardController', 'execute', ['GET']);
        
        $match = $this->router->match('/admin/dashboard', 'GET');
        
        expect($match)->not->toBeNull();
        expect($match['controller'])->toBe('Admin\\DashboardController');
    });
    
    it('handles CMS-style dynamic routes', function () {
        $this->router->addRoute('cms_page', '/:urlkey', 'CmsController', 'view', ['GET']);
        
        $match = $this->router->match('/about-us', 'GET');
        
        expect($match)->not->toBeNull();
        expect($match['params']['urlkey'])->toBe('about-us');
    });
    
    it('maintains route order priority', function () {
        // More specific routes should be registered first
        $this->router->addRoute('admin_home', '/admin', 'AdminController', 'index', ['GET']);
        $this->router->addRoute('cms_page', '/:urlkey', 'CmsController', 'view', ['GET']);
        
        $matchAdmin = $this->router->match('/admin', 'GET');
        $matchCms = $this->router->match('/about', 'GET');
        
        expect($matchAdmin['controller'])->toBe('AdminController');
        expect($matchCms['controller'])->toBe('CmsController');
    });
    
});
