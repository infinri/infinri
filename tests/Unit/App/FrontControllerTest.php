<?php

declare(strict_types=1);

use Infinri\Core\App\FrontController;
use Infinri\Core\App\FastRouter;
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
        $this->frontController = new FrontController(
            $this->router,
            $this->objectManager,
            new Request()  // Add mock Request as third argument
        );
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
    
    it('passes parameters to controller', function () {
        // Create a test controller class
        $testControllerClass = new class {
            public function execute(Request $request): Response {
                // Get the 'id' parameter from the request
                $id = $request->getParam('id', '');
                $response = new Response();
                return $response->setBody('ID: ' . $id);
            }
        };
        
        // Add route with anonymous class
        $className = get_class($testControllerClass);
        $this->router->addRoute('item', '/item/:id', $className, 'execute');
        
        // Register the controller in the container
        $this->container->set($className, $testControllerClass);
        
        $request = new Request([], [], ['REQUEST_URI' => '/item/123', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getBody())->toBe('ID: 123');
    });
    
    it('returns 404 for missing action', function () {
        $this->router->addRoute('test', '/test', TestController::class, 'nonExistentAction');
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(404);
    });
    
    it('handles controller exceptions', function () {
        $this->router->addRoute('test', '/test', 'NonExistentController');
        
        $request = new Request([], [], ['REQUEST_URI' => '/test', 'REQUEST_METHOD' => 'GET']);
        $response = $this->frontController->dispatch($request);
        
        expect($response->getStatusCode())->toBe(500);
    });
    
});
