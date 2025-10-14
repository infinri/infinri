<?php

declare(strict_types=1);

use Infinri\Core\App\Request;

describe('Request', function () {
    
    it('can get request method', function () {
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST']);
        
        expect($request->getMethod())->toBe('POST');
    });
    
    it('defaults to GET method', function () {
        $request = new Request([], [], []);
        
        expect($request->getMethod())->toBe('GET');
    });
    
    it('can check if request is POST', function () {
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST']);
        
        expect($request->isPost())->toBeTrue();
        expect($request->isGet())->toBeFalse();
    });
    
    it('can check if request is GET', function () {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET']);
        
        expect($request->isGet())->toBeTrue();
        expect($request->isPost())->toBeFalse();
    });
    
    it('can get path info', function () {
        $request = new Request([], [], ['REQUEST_URI' => '/product/view']);
        
        expect($request->getPathInfo())->toBe('/product/view');
    });
    
    it('strips query string from path info', function () {
        $request = new Request([], [], ['REQUEST_URI' => '/product/view?id=123']);
        
        expect($request->getPathInfo())->toBe('/product/view');
    });
    
    it('can get query parameter', function () {
        $request = new Request(['id' => '123'], []);
        
        expect($request->getQuery('id'))->toBe('123');
        expect($request->getQuery('missing', 'default'))->toBe('default');
    });
    
    it('can get POST parameter', function () {
        $request = new Request([], ['name' => 'John']);
        
        expect($request->getPost('name'))->toBe('John');
        expect($request->getPost('missing', 'default'))->toBe('default');
    });
    
    it('can get parameter from query first', function () {
        $request = new Request(['key' => 'query'], ['key' => 'post']);
        
        expect($request->getParam('key'))->toBe('query');
    });
    
    it('falls back to POST if not in query', function () {
        $request = new Request([], ['key' => 'post']);
        
        expect($request->getParam('key'))->toBe('post');
    });
    
    it('can set and get route parameters', function () {
        $request = new Request();
        $request->setParam('id', '456');
        
        expect($request->getParam('id'))->toBe('456');
        expect($request->getParams())->toHaveKey('id');
    });
    
    it('can detect AJAX requests', function () {
        $request = new Request([], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        
        expect($request->isAjax())->toBeTrue();
    });
    
    it('can get client IP', function () {
        $request = new Request([], [], ['REMOTE_ADDR' => '192.168.1.1']);
        
        expect($request->getClientIp())->toBe('192.168.1.1');
    });
    
    it('can get HTTP host', function () {
        $request = new Request([], [], ['HTTP_HOST' => 'example.com']);
        
        expect($request->getHttpHost())->toBe('example.com');
    });
    
    it('can detect HTTPS', function () {
        $request = new Request([], [], ['HTTPS' => 'on']);
        
        expect($request->isSecure())->toBeTrue();
    });
    
});
