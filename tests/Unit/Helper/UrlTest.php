<?php

declare(strict_types=1);

use Infinri\Core\Helper\Url;
use Infinri\Core\Model\Url\Builder;

describe('URL Helper', function () {
    
    beforeEach(function () {
        $builder = new Builder(null, 'http://localhost');
        $this->helper = new Url($builder);
    });
    
    it('can generate URL', function () {
        $url = $this->helper->url('/about');
        
        expect($url)->toBe('http://localhost/about');
    });
    
    it('can generate URL with parameters', function () {
        $url = $this->helper->url('/product/:id', ['id' => 123]);
        
        expect($url)->toBe('http://localhost/product/123');
    });
    
    it('can generate URL with query string', function () {
        $url = $this->helper->url('/search', [], ['q' => 'test']);
        
        expect($url)->toBe('http://localhost/search?q=test');
    });
    
    it('can generate route URL', function () {
        $url = $this->helper->route('/products');
        
        expect($url)->toBe('http://localhost/products');
    });
    
    it('can generate absolute URL', function () {
        $url = $this->helper->absolute('/page');
        
        expect($url)->toBe('http://localhost/page');
    });
    
    it('can generate secure URL', function () {
        $url = $this->helper->secure('/checkout');
        
        expect($url)->toStartWith('https://');
    });
    
    it('can get base URL', function () {
        $base = $this->helper->base();
        
        expect($base)->toBe('http://localhost');
    });
    
    it('can get builder instance', function () {
        $builder = $this->helper->getBuilder();
        
        expect($builder)->toBeInstanceOf(Builder::class);
    });
    
    it('works without explicit builder', function () {
        $helper = new Url();
        
        $url = $helper->url('/test');
        
        expect($url)->toBeString();
        expect($url)->toContain('/test');
    });
    
    it('can chain multiple URL generations', function () {
        $url1 = $this->helper->url('/page1');
        $url2 = $this->helper->url('/page2');
        $url3 = $this->helper->url('/page3');
        
        expect($url1)->toBe('http://localhost/page1');
        expect($url2)->toBe('http://localhost/page2');
        expect($url3)->toBe('http://localhost/page3');
    });
    
    it('handles parameters in helper methods', function () {
        $url = $this->helper->url('/item/:id', ['id' => 999], ['view' => 'full']);
        
        expect($url)->toBe('http://localhost/item/999?view=full');
    });
    
    it('generates consistent URLs', function () {
        $url1 = $this->helper->url('/test');
        $url2 = $this->helper->url('/test');
        
        expect($url1)->toBe($url2);
    });
});
