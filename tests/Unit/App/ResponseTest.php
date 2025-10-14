<?php

declare(strict_types=1);

use Infinri\Core\App\Response;

describe('Response', function () {
    
    beforeEach(function () {
        $this->response = new Response();
    });
    
    it('can set and get body', function () {
        $this->response->setBody('Hello World');
        
        expect($this->response->getBody())->toBe('Hello World');
    });
    
    it('can append to body', function () {
        $this->response->setBody('Hello');
        $this->response->appendBody(' World');
        
        expect($this->response->getBody())->toBe('Hello World');
    });
    
    it('defaults to 200 status code', function () {
        expect($this->response->getStatusCode())->toBe(200);
    });
    
    it('can set status code', function () {
        $this->response->setStatusCode(404);
        
        expect($this->response->getStatusCode())->toBe(404);
    });
    
    it('can set headers', function () {
        $this->response->setHeader('X-Custom', 'value');
        
        expect($this->response->getHeader('X-Custom'))->toBe('value');
    });
    
    it('can get all headers', function () {
        $this->response->setHeader('X-One', 'value1');
        $this->response->setHeader('X-Two', 'value2');
        
        $headers = $this->response->getHeaders();
        
        expect($headers)->toHaveKey('X-One');
        expect($headers)->toHaveKey('X-Two');
    });
    
    it('can set content type', function () {
        $this->response->setContentType('application/json');
        
        expect($this->response->getHeader('Content-Type'))->toBe('application/json');
    });
    
    it('can set redirect', function () {
        $this->response->setRedirect('/new-page', 301);
        
        expect($this->response->getStatusCode())->toBe(301);
        expect($this->response->getHeader('Location'))->toBe('/new-page');
    });
    
    it('can set JSON response', function () {
        $this->response->setJson(['key' => 'value']);
        
        expect($this->response->getHeader('Content-Type'))->toBe('application/json');
        expect($this->response->getBody())->toBe('{"key":"value"}');
    });
    
    it('can set not found status', function () {
        $this->response->setNotFound();
        
        expect($this->response->getStatusCode())->toBe(404);
    });
    
    it('can set server error status', function () {
        $this->response->setServerError();
        
        expect($this->response->getStatusCode())->toBe(500);
    });
    
    it('can set forbidden status', function () {
        $this->response->setForbidden();
        
        expect($this->response->getStatusCode())->toBe(403);
    });
    
});
