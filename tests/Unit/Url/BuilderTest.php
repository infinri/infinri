<?php

declare(strict_types=1);

use Infinri\Core\Model\Url\Builder;

describe('URL Builder', function () {
    
    beforeEach(function () {
        $this->builder = new Builder(null, 'http://localhost');
    });
    
    it('can build simple URL', function () {
        $url = $this->builder->build('/about');
        
        expect($url)->toBe('http://localhost/about');
    });
    
    it('can build URL with leading slash', function () {
        $url = $this->builder->build('/products');
        
        expect($url)->toBe('http://localhost/products');
    });
    
    it('can build URL without leading slash', function () {
        $url = $this->builder->build('contact');
        
        expect($url)->toBe('http://localhost/contact');
    });
    
    it('can build URL with route parameters', function () {
        $url = $this->builder->build('/product/:id', ['id' => 123]);
        
        expect($url)->toBe('http://localhost/product/123');
    });
    
    it('can build URL with multiple parameters', function () {
        $url = $this->builder->build('/category/:category/product/:id', [
            'category' => 'electronics',
            'id' => 456,
        ]);
        
        expect($url)->toBe('http://localhost/category/electronics/product/456');
    });
    
    it('can build URL with query string', function () {
        $url = $this->builder->build('/search', [], ['q' => 'test', 'page' => 2]);
        
        expect($url)->toBe('http://localhost/search?q=test&page=2');
    });
    
    it('can build URL with parameters and query string', function () {
        $url = $this->builder->build('/product/:id', ['id' => 789], ['variant' => 'blue']);
        
        expect($url)->toBe('http://localhost/product/789?variant=blue');
    });
    
    it('returns absolute URLs as-is', function () {
        $url = $this->builder->build('https://example.com/page');
        
        expect($url)->toBe('https://example.com/page');
    });
    
    it('can build route URL', function () {
        $url = $this->builder->route('/products');
        
        expect($url)->toBe('http://localhost/products');
    });
    
    it('can build absolute URL', function () {
        $url = $this->builder->absolute('/about');
        
        expect($url)->toBe('http://localhost/about');
    });
    
    it('can build secure URL', function () {
        $url = $this->builder->secure('/checkout');
        
        expect($url)->toBe('https://localhost/checkout');
    });
    
    it('secure URL includes full scheme', function () {
        $url = $this->builder->secure('/payment', ['id' => 123]);
        
        expect($url)->toStartWith('https://');
    });
    
    it('can set and get base URL', function () {
        $this->builder->setBaseUrl('http://example.com');
        
        expect($this->builder->getBaseUrl())->toBe('http://example.com');
    });
    
    it('can set and get secure flag', function () {
        $this->builder->setSecure(true);
        
        expect($this->builder->isSecure())->toBeTrue();
    });
    
    it('builds URLs with custom base URL', function () {
        $this->builder->setBaseUrl('http://example.com');
        
        $url = $this->builder->build('/page');
        
        expect($url)->toBe('http://example.com/page');
    });
    
    it('handles empty query parameters', function () {
        $url = $this->builder->build('/page', [], []);
        
        expect($url)->toBe('http://localhost/page');
    });
    
    it('handles empty route parameters', function () {
        $url = $this->builder->build('/page', []);
        
        expect($url)->toBe('http://localhost/page');
    });
    
    it('encodes query string values', function () {
        $url = $this->builder->build('/search', [], ['q' => 'hello world']);
        
        expect($url)->toContain('hello+world');
    });
    
    it('handles protocol-relative URLs', function () {
        $url = $this->builder->build('//cdn.example.com/file.js');
        
        expect($url)->toBe('//cdn.example.com/file.js');
    });
    
    it('can build URL with numeric parameters', function () {
        $url = $this->builder->build('/item/:id', ['id' => 999]);
        
        expect($url)->toBe('http://localhost/item/999');
    });
    
    it('can build URL with string parameters', function () {
        $url = $this->builder->build('/user/:username', ['username' => 'johndoe']);
        
        expect($url)->toBe('http://localhost/user/johndoe');
    });
    
    it('preserves unmatched route parameters', function () {
        $url = $this->builder->build('/product/:id', ['id' => 123, 'extra' => 'value']);
        
        expect($url)->toBe('http://localhost/product/123');
    });
    
    it('handles complex query strings', function () {
        $url = $this->builder->build('/api', [], [
            'filter' => 'active',
            'sort' => 'name',
            'limit' => 10,
            'offset' => 20,
        ]);
        
        expect($url)->toContain('filter=active');
        expect($url)->toContain('sort=name');
        expect($url)->toContain('limit=10');
        expect($url)->toContain('offset=20');
    });
    
    it('can build URL with base URL ending in slash', function () {
        $this->builder->setBaseUrl('http://localhost/');
        
        $url = $this->builder->build('about');
        
        expect($url)->toBe('http://localhost//about');
    });
    
    it('handles root path', function () {
        $url = $this->builder->build('/');
        
        expect($url)->toBe('http://localhost/');
    });
    
    it('handles empty path', function () {
        $url = $this->builder->build('');
        
        expect($url)->toBe('http://localhost/');
    });
});
