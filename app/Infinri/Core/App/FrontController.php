<?php
declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Controller\AbstractController;
use Infinri\Core\Helper\Logger;
use Infinri\Core\App\Middleware\SecurityHeadersMiddleware;

/**
 * Front Controller
 * 
 * Entry point for all HTTP requests. Dispatches to appropriate controller.
 */
class FrontController
{
    /**
     * Allowed controller namespace prefixes
     * Only classes within these namespaces can be instantiated
     */
    private const ALLOWED_CONTROLLER_NAMESPACES = [
        'Infinri\\Core\\Controller\\',
        'Infinri\\Cms\\Controller\\',
        'Infinri\\Admin\\Controller\\',
        'Infinri\\Auth\\Controller\\',
        'Infinri\\Theme\\Controller\\',
        'Infinri\\Menu\\Controller\\',
        'Tests\\',  // Allow test controllers
    ];
    
    private ?string $resolvedControllerClass = null;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly ObjectManager $objectManager,
        private readonly Request $request,
        private readonly SecurityHeadersMiddleware $securityHeaders,
        private readonly Middleware\AuthenticationMiddleware $authMiddleware
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
                
                $response->setNotFound()->setBody('404 - Page Not Found');
                return $this->securityHeaders->handle($request, $response);
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
                // Sanitize value to prevent path traversal and injection
                $sanitizedValue = $this->sanitizeClassName($value);
                
                // Capitalize first letter for class names
                $className = ucfirst($sanitizedValue);
                $controllerClass = str_replace(":{$key}", $className, $controllerClass);
            }
            
            // Validate controller namespace for security
            if (!$this->isValidControllerNamespace($controllerClass)) {
                Logger::error('Attempted controller class injection', [
                    'controller' => $controllerClass,
                    'uri' => $uri
                ]);
                
                $response->setForbidden()->setBody('403 - Forbidden');
                return $this->securityHeaders->handle($request, $response);
            }

            // Check authentication for admin routes
            if (str_starts_with($uri, '/admin')) {
                $response = $this->authMiddleware->handle($request, $response);
                // If middleware returned a redirect/forbidden response, return it
                if ($response->getStatusCode() !== 200) {
                    return $this->securityHeaders->handle($request, $response);
                }
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
                
                $response->setNotFound()->setBody('404 - Action Not Found');
                return $this->securityHeaders->handle($request, $response);
            }
            
            Logger::debug('Executing action', [
                'controller' => get_class($controller),
                'action' => $action
            ]);

            $response = $controller->$action($request);
            
            // Apply security headers to all responses
            return $this->securityHeaders->handle($request, $response);

        } catch (\Throwable $e) {
            // Log exception
            Logger::exception($e, 'Exception during request dispatch');
            
            // Handle errors
            $response->setServerError()->setBody($this->formatError($e));
            return $this->securityHeaders->handle($request, $response);
        }
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

    /**
     * Sanitize class name part to prevent injection
     *
     * @param string $value
     * @return string
     */
    private function sanitizeClassName(string $value): string
    {
        // Remove any characters that aren't alphanumeric or underscore
        // This prevents path traversal (../) and namespace injection (\)
        return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
    }
    
    /**
     * Validate that controller class is within allowed namespaces
     *
     * @param string $controllerClass
     * @return bool
     */
    private function isValidControllerNamespace(string $controllerClass): bool
    {
        // Allow anonymous classes (for testing)
        if (str_starts_with($controllerClass, 'class@anonymous')) {
            return true;
        }

        // Allow classes in allowed namespaces
        foreach (self::ALLOWED_CONTROLLER_NAMESPACES as $namespace) {
            if (str_starts_with($controllerClass, $namespace)) {
                return true;
            }
        }
        
        // SECURITY: Global namespace bypass removed
        // Controllers MUST be in whitelisted namespaces for security
        
        return false;
    }
    
    /**
     * Format error message
     *
     * @param \Throwable $e
     * @return string
     */
    private function formatError(\Throwable $e): string
    {
        // Check environment to determine error display level
        $env = getenv('APP_ENV') ?: 'production';
        $isDevelopment = in_array($env, ['development', 'dev', 'local']);
        
        if ($isDevelopment) {
            // Development: Show detailed error information
            return sprintf(
                "500 - Internal Server Error\n\n%s\n\nFile: %s:%d\n\nTrace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
        } else {
            // Production: Show generic error message only
            return "500 - Internal Server Error\n\nAn unexpected error occurred. Please try again later.";
        }
    }
}
