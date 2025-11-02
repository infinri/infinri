<?php

declare(strict_types=1);

namespace Tests\Unit\App;

use Infinri\Core\App\FrontController;
use Infinri\Core\App\Dispatcher;
use Infinri\Core\App\FastRouter;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\App\Middleware\SecurityHeadersMiddleware;
use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Controller\AbstractController;
use Psr\Container\ContainerInterface;
use Mockery;

// Test controller  
class TestController extends AbstractController
{
    public function execute(): Response
    {
        return (new Response())->setBody('Test Response');
    }
    
    public function withParam(): Response
    {
        $id = $this->request->getParam('id');
        return (new Response())->setBody("ID: {$id}");
    }
}

describe('FrontController', function () {
    
    beforeEach(function () {
        // Create simple ObjectManager stub with PSR-11 interface
        // Make it throw exception so FrontController falls back to manual instantiation
        $container = new class implements ContainerInterface {
            private $entries = [];
            
            public function has(string $id): bool { 
                return array_key_exists($id, $this->entries); 
            }
            public function get(string $id) { 
                if (!$this->has($id)) {
                    throw new \Exception('Test: Container entry not found'); 
                }
                return $this->entries[$id];
            }
            public function set(string $id, $value) {
                $this->entries[$id] = $value;
            }
        };
        
        ObjectManager::reset();
        $this->objectManager = ObjectManager::setInstance($container);
        $this->container = $container;
        
        $this->router = new FastRouter();
        $this->request = new Request();
        
        // Phase 3.1: Create Dispatcher for SOLID refactoring
        $this->dispatcher = new Dispatcher($this->objectManager, $this->request);
        
        // Mock AuthenticationMiddleware dependencies
        $rememberTokenService = Mockery::mock(\Infinri\Admin\Service\RememberTokenService::class);
        $adminUserResource = Mockery::mock(\Infinri\Admin\Model\ResourceModel\AdminUser::class);
        
        $this->frontController = new FrontController(
            $this->router,
            $this->dispatcher,
            $this->request,
            new SecurityHeadersMiddleware(),
            new \Infinri\Core\App\Middleware\AuthenticationMiddleware($rememberTokenService, $adminUserResource)
        );
    });
    
    afterEach(function () {
        ObjectManager::reset();
    });
    
    it('can dispatch request to controller', function () {
        // Use /static path to bypass redirect/rewrite checks
        $this->router->addRoute('test', '/static/test', \Tests\Unit\App\TestController::class);
        
        $request = new Request([], [], ['REQUEST_URI' => '/static/test', 'REQUEST_METHOD' => 'GET']);
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
    
    it('passes parameters to controller', function () {
        // Use /static to bypass redirect/rewrite checks
        $this->router->addRoute('item', '/static/item/:id', \Tests\Unit\App\TestController::class, 'withParam');
        
        $request = new Request([], [], ['REQUEST_URI' => '/static/item/123', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getBody())->toBe('ID: 123');
    });
    
    it('returns 404 for missing action', function () {
        $this->router->addRoute('test', '/test', \Tests\Unit\App\TestController::class, 'nonExistentAction');
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(404);
    });
    
    it('handles controller exceptions', function () {
        // Use a non-existent controller in Tests namespace (passes namespace check but doesn't exist)
        $this->router->addRoute('test', '/static/test', 'Tests\\Unit\\App\\NonExistentController');
        
        $request = new Request([], [], ['REQUEST_URI' => '/static/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(500);
    });
    
});
