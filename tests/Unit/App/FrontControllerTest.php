<?php

declare(strict_types=1);

use Infinri\Core\App\FrontController;
use Infinri\Core\App\Router;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Controller\AbstractController;
use Psr\Container\ContainerInterface;

// Test controller
class TestController extends AbstractController
{
    public function execute(): Response
    {
        return $this->response->setBody('Test Response');
    }
    
    public function withParam(): Response
    {
        $id = $this->request->getParam('id');
        return $this->response->setBody("ID: {$id}");
    }
}

describe('FrontController', function () {
    
    beforeEach(function () {
        // Create simple ObjectManager stub with PSR-11 interface
        $container = new class implements ContainerInterface {
            public function has(string $id): bool { return false; }
            public function get(string $id) { return null; }
        };
        
        ObjectManager::reset();
        $this->objectManager = ObjectManager::setInstance($container);
        
        $this->router = new Router();
        $this->frontController = new FrontController($this->router, $this->objectManager);
    });
    
    afterEach(function () {
        ObjectManager::reset();
    });
    
    it('can dispatch request to controller', function () {
        $this->router->addRoute('test', '/test', TestController::class);
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getBody())->toBe('Test Response');
        expect($response->getStatusCode())->toBe(200);
    });
    
    it('returns 404 for non-matching route', function () {
        $request = new Request([], [], ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(404);
        expect($response->getBody())->toContain('404');
    });
    
    it('passes route parameters to controller', function () {
        $this->router->addRoute('param', '/item/:id', TestController::class, 'withParam');
        
        $request = new Request([], [], ['REQUEST_URI' => '/item/123', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getBody())->toBe('ID: 123');
    });
    
    it('returns 500 for missing action', function () {
        $this->router->addRoute('test', '/test', TestController::class, 'nonExistentAction');
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(500);
        expect($response->getBody())->toContain('Action');
    });
    
    it('handles controller exceptions', function () {
        $this->router->addRoute('test', '/test', 'NonExistentController');
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(500);
    });
    
});
