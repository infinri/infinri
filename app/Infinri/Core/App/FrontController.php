<?php
declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Controller\AbstractController;
use Infinri\Core\Helper\Logger;

/**
 * Front Controller
 * 
 * Entry point for all HTTP requests. Dispatches to appropriate controller.
 */
class FrontController
{
    private ?string $resolvedControllerClass = null;

    public function __construct(
        private readonly Router $router,
        private readonly ObjectManager $objectManager,
        private readonly Request $request
    ) {
    }

    /**
     * Dispatch request to controller
     *
     * @param Request $request
     * @return Response
     */
    public function dispatch(Request $request): Response
    {
        $response = new Response();

        try {
            $uri = $request->getPathInfo();
            $method = $request->getMethod();
            
            Logger::debug('Attempting to match route', [
                'uri' => $uri,
                'method' => $method
            ]);
            
            // Match route
            $match = $this->router->match($uri, $method);

            if ($match === null) {
                // Log 404 - Route not found
                Logger::warning('404 - Route not found', [
                    'uri' => $uri,
                    'method' => $method,
                    'available_routes' => 'Check routes.xml files in enabled modules'
                ]);
                
                return $response
                    ->setNotFound()
                    ->setBody('404 - Page Not Found');
            }
            
            Logger::info('Route matched successfully', [
                'uri' => $uri,
                'controller' => $match['controller'],
                'action' => $match['action'],
                'params' => $match['params']
            ]);

            // Set route parameters in request
            foreach ($match['params'] as $key => $value) {
                $request->setParam($key, $value);
            }
            
            // Replace placeholders in controller class name with matched parameters
            // E.g., "Infinri\Cms\Controller\Adminhtml\:controller\:action" 
            //    -> "Infinri\Cms\Controller\Adminhtml\Page\Index"
            $controllerClass = $match['controller'];
            foreach ($match['params'] as $key => $value) {
                // Capitalize first letter for class names
                $className = ucfirst($value);
                $controllerClass = str_replace(":{$key}", $className, $controllerClass);
            }

            // Store resolved controller class and create controller
            $this->resolvedControllerClass = $controllerClass;
            $controller = $this->createController($controllerClass);

            // Get action method name
            $action = $match['action'];

            if (!method_exists($controller, $action)) {
                Logger::error('Action not found in controller', [
                    'controller' => get_class($controller),
                    'action' => $action,
                    'available_methods' => get_class_methods($controller)
                ]);
                
                return $response
                    ->setNotFound()
                    ->setBody('404 - Action Not Found');
            }
            
            Logger::debug('Executing action', [
                'controller' => get_class($controller),
                'action' => $action
            ]);

            return $controller->$action($request);

        } catch (\Throwable $e) {
            // Log exception
            Logger::exception($e, 'Exception during request dispatch');
            
            // Handle errors
            return $response
                ->setServerError()
                ->setBody($this->formatError($e));
        }

        return $response;
    }

    /**
     * Create controller instance
     */
    private function createController(string $controllerClass): object
    {
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller class not found: {$controllerClass}");
        }

        try {
            // Try ObjectManager for proper dependency injection
            return $this->objectManager->create($controllerClass);
        } catch (\Throwable $e) {
            // Fallback for test controllers or simple controllers
            // that expect Request and Response in constructor
            if (method_exists($controllerClass, '__construct')) {
                $reflection = new \ReflectionClass($controllerClass);
                $constructor = $reflection->getConstructor();
                
                if ($constructor && $constructor->getNumberOfParameters() <= 2) {
                    // Check if parameters are Request/Response types (legacy pattern)
                    $params = $constructor->getParameters();
                    $isLegacyController = true;
                    
                    foreach ($params as $param) {
                        $type = $param->getType();
                        if ($type instanceof \ReflectionNamedType) {
                            $typeName = $type->getName();
                            if ($typeName !== Request::class && $typeName !== Response::class) {
                                $isLegacyController = false;
                                break;
                            }
                        }
                    }
                    
                    if ($isLegacyController) {
                        // Legacy pattern: __construct(Request $request, Response $response)
                        return new $controllerClass($this->request, new Response());
                    }
                }
            }
            
            // Re-throw if we can't handle it
            throw $e;
        }
    }

    private function getControllerClass(): string
    {
        // Implement logic to get the controller class internally
        // For demonstration purposes, we'll just return a hardcoded class
        return 'Infinri\Core\Controller\ExampleController';
    }

    /**
     * Format error message
     *
     * @param \Throwable $e
     * @return string
     */
    private function formatError(\Throwable $e): string
    {
        // In production, this should log the error and show a generic message
        // For development, show detailed error
        return sprintf(
            "500 - Internal Server Error\n\n%s\n\nFile: %s:%d\n\nTrace:\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}
