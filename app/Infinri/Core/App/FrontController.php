<?php
declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Controller\AbstractController;

/**
 * Front Controller
 * 
 * Entry point for all HTTP requests. Dispatches to appropriate controller.
 */
class FrontController
{
    public function __construct(
        private readonly Router $router,
        private readonly ObjectManager $objectManager
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
            // Match route
            $match = $this->router->match(
                $request->getPathInfo(),
                $request->getMethod()
            );

            if ($match === null) {
                return $response
                    ->setNotFound()
                    ->setBody('404 - Page Not Found');
            }

            // Set route parameters in request
            foreach ($match['params'] as $key => $value) {
                $request->setParam($key, $value);
            }

            // Create controller
            $controller = $this->createController(
                $match['controller'],
                $request,
                $response
            );

            // Execute action
            $action = $match['action'];
            
            if (!method_exists($controller, $action)) {
                return $response
                    ->setServerError()
                    ->setBody("500 - Action '{$action}' not found in controller");
            }

            return $controller->$action();

        } catch (\Throwable $e) {
            // Handle errors
            return $response
                ->setServerError()
                ->setBody($this->formatError($e));
        }
    }

    /**
     * Create controller instance
     *
     * @param string $controllerClass
     * @param Request $request
     * @param Response $response
     * @return AbstractController
     */
    private function createController(
        string $controllerClass,
        Request $request,
        Response $response
    ): AbstractController {
        // Try to get from ObjectManager first
        if ($this->objectManager->has($controllerClass)) {
            $controller = $this->objectManager->create($controllerClass);
            
            if ($controller instanceof AbstractController) {
                return $controller;
            }
        }

        // Fallback: direct instantiation
        if (class_exists($controllerClass)) {
            return new $controllerClass($request, $response);
        }

        throw new \RuntimeException("Controller class '{$controllerClass}' not found");
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
