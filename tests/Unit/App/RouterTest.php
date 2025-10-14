<?php

declare(strict_types=1);

use Infinri\Core\App\Router;

describe('Router', function () {
    
    beforeEach(function () {
        $this->router = new Router();
    });
    
    it('can add and match simple route', function () {
        $this->router->addRoute('home', '/', 'HomeController', 'index');
        
        $match = $this->router->match('/');
        
        expect($match)->not->toBeNull();
        expect($match['controller'])->toBe('HomeController');
        expect($match['action'])->toBe('index');
    });
    
    it('returns null for non-matching route', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        
        $match = $this->router->match('/nonexistent');
        
        expect($match)->toBeNull();
    });
    
    it('can match route with parameters', function () {
        $this->router->addRoute('product', '/product/view/:id', 'ProductController');
        
        $match = $this->router->match('/product/view/123');
        
        expect($match)->not->toBeNull();
        expect($match['params'])->toHaveKey('id');
        expect($match['params']['id'])->toBe('123');
    });
    
    it('can match route with multiple parameters', function () {
        $this->router->addRoute('category', '/category/:name/page/:page', 'CategoryController');
        
        $match = $this->router->match('/category/electronics/page/2');
        
        expect($match)->not->toBeNull();
        expect($match['params']['name'])->toBe('electronics');
        expect($match['params']['page'])->toBe('2');
    });
    
    it('checks HTTP method', function () {
        $this->router->addRoute('create', '/product/create', 'ProductController', 'create', ['POST']);
        
        $matchGet = $this->router->match('/product/create', 'GET');
        $matchPost = $this->router->match('/product/create', 'POST');
        
        expect($matchGet)->toBeNull();
        expect($matchPost)->not->toBeNull();
    });
    
    it('can generate URL from route name', function () {
        $this->router->addRoute('product', '/product/view/:id', 'ProductController');
        
        $url = $this->router->generate('product', ['id' => '456']);
        
        expect($url)->toBe('/product/view/456');
    });
    
    it('returns null for non-existent route name', function () {
        $url = $this->router->generate('nonexistent');
        
        expect($url)->toBeNull();
    });
    
    it('can get all routes', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        $this->router->addRoute('about', '/about', 'AboutController');
        
        $routes = $this->router->getRoutes();
        
        expect($routes)->toHaveKey('home');
        expect($routes)->toHaveKey('about');
    });
    
    it('defaults action to execute', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        
        $match = $this->router->match('/');
        
        expect($match['action'])->toBe('execute');
    });
    
    it('allows both GET and POST by default', function () {
        $this->router->addRoute('home', '/', 'HomeController');
        
        $matchGet = $this->router->match('/', 'GET');
        $matchPost = $this->router->match('/', 'POST');
        
        expect($matchGet)->not->toBeNull();
        expect($matchPost)->not->toBeNull();
    });
    
});
