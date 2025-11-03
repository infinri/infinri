<?php

declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Helper\Logger;

/**
 * Handles controller instantiation and action execution
 */
class Dispatcher
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
        'Infinri\\Seo\\Controller\\',
        'Tests\\',  // Allow test controllers
    ];

    public function __construct(
        private readonly ObjectManager $objectManager,
        private readonly Request       $request
    ) {}

    /**
     * Dispatch to controller action
     *
     * @param Route $route Matched route
     * @return Response
     * @throws \RuntimeException|\ReflectionException
     */
    public function dispatch(Route $route): Response
    {
        // Get controller class with placeholders replaced
        $controllerClass = $route->getControllerClass([$this, 'sanitizeClassName']);

        // Validate controller namespace for security
        if (!$this->isValidControllerNamespace($controllerClass)) {
            Logger::error('Attempted controller class injection', [
                'controller' => $controllerClass
            ]);

            throw new \RuntimeException("Invalid controller namespace: {$controllerClass}");
        }

        // Set route parameters in request
        foreach ($route->params as $key => $value) {
            $this->request->setParam($key, $value);
        }

        // Create controller instance
        $controller = $this->createController($controllerClass);

        // Verify action exists
        if (!method_exists($controller, $route->action)) {
            Logger::error('Action not found in controller', [
                'controller' => get_class($controller),
                'action' => $route->action,
                'available_methods' => get_class_methods($controller)
            ]);

            throw new \RuntimeException("Action not found: {$route->action}");
        }

        Logger::debug('Executing action', [
            'controller' => get_class($controller),
            'action' => $route->action
        ]);

        // Execute action - check if method expects Request parameter
        $action = $route->action;
        $reflection = new \ReflectionMethod($controller, $action);
        $parameters = $reflection->getParameters();

        // If method accepts a Request parameter, pass it
        if (count($parameters) > 0 && $parameters[0]->getType()?->getName() === Request::class) {
            return $controller->$action($this->request);
        }

        // Otherwise call without parameters (e.g., AbstractController subclasses)
        return $controller->$action();
    }

    /**
     * Create controller instance
     *
     * @param string $controllerClass
     * @return object
     * @throws \RuntimeException|\Throwable
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
            // Fallback for test controllers or simple controllers that expect Request and Response in constructor
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
     * Public so it can be passed to Route::getControllerClass()
     *
     * @param string $value
     * @return string
     */
    public function sanitizeClassName(string $value): string
    {
        // Remove any characters that aren't alphanumeric or underscore. This prevents path traversal (../) and namespace injection (\)
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

        return false;
    }
}
