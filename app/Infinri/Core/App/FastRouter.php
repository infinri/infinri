<?php
declare(strict_types=1);

namespace Infinri\Core\App;

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

/**
 * FastRoute-based Router
 */
class FastRouter implements RouterInterface
{
    /**
     * @var array<string, array<string, mixed>> Registered routes (for reference/generation)
     */
    private array $routes = [];

    /**
     * @var Dispatcher|null Compiled FastRoute dispatcher
     */
    private ?Dispatcher $dispatcher = null;

    /**
     * @var bool Whether routes have been modified since last compile
     */
    private bool $dirty = true;

    /**
     * Register a route
     *
     * @param string $name Route name
     * @param string $path URL pattern (e.g., '/product/view/:id')
     * @param string $controller Controller class
     * @param string $action Action method
     * @param array<string, mixed> $methods Allowed HTTP methods
     * @return $this
     */
    public function addRoute(
        string $name,
        string $path,
        string $controller,
        string $action = 'execute',
        array  $methods = ['GET', 'POST']
    ): self
    {
        $this->routes[$name] = [
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'methods' => $methods,
            'specificity' => $this->calculateSpecificity($path),
        ];

        $this->dirty = true;

        return $this;
    }

    /**
     * Calculate route specificity score (higher = more specific)
     * More specific routes should be matched first
     *
     * @param string $path
     * @return int
     */
    private function calculateSpecificity(string $path): int
    {
        $score = 0;

        // Split path into segments
        $segments = explode('/', trim($path, '/'));

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '*') {
                // Wildcard - least specific
                $score -= 100;
            } elseif (str_starts_with($segment, ':')) {
                // Parameter segment (e.g., :id) - somewhat specific
                $score += 1;
            } else {
                // Static segment (e.g., "admin", "auth") - most specific
                $score += 100;
            }
        }

        // Prefer routes with more segments (more specific paths)
        $score += count($segments);

        return $score;
    }

    /**
     * Match URL to route
     *
     * @param string $path Request path
     * @param string $method HTTP method
     * @return array|null ['controller' => ..., 'action' => ..., 'params' => [...]] or null
     */
    public function match(string $path, string $method = 'GET'): ?array
    {
        // Build dispatcher if needed
        if ($this->dirty || $this->dispatcher === null) {
            $this->buildDispatcher();
        }

        $routeInfo = $this->dispatcher->dispatch($method, $path);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $params = $routeInfo[2];

                return [
                    'name' => $handler['name'],
                    'controller' => $handler['controller'],
                    'action' => $handler['action'],
                    'params' => $params,
                ];

            case Dispatcher::METHOD_NOT_ALLOWED:
                // Route exists but method not allowed
                return null;

            case Dispatcher::NOT_FOUND:
            default:
                return null;
        }
    }

    /**
     * Build FastRoute dispatcher from registered routes
     *
     * @return void
     */
    private function buildDispatcher(): void
    {
        // Sort routes by specificity (most specific first). This ensures more specific routes are checked before generic catch-alls
        $sortedRoutes = $this->routes;
        uasort($sortedRoutes, function ($a, $b) {
            return ($b['specificity'] ?? 0) <=> ($a['specificity'] ?? 0);
        });

        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) use ($sortedRoutes) {
            foreach ($sortedRoutes as $name => $route) {
                // Convert custom :param syntax to FastRoute {param} syntax
                $fastRoutePath = $this->convertToFastRoutePattern($route['path']);

                // Register for each allowed method
                foreach ($route['methods'] as $method) {
                    $r->addRoute($method, $fastRoutePath, [
                        'name' => $name,
                        'controller' => $route['controller'],
                        'action' => $route['action'],
                    ]);
                }
            }
        });

        $this->dirty = false;
    }

    /**
     * Convert custom route pattern to FastRoute pattern
     *
     * Custom: /product/view/:id
     * FastRoute: /product/view/{id}
     *
     * @param string $path
     * @return string
     */
    private function convertToFastRoutePattern(string $path): string
    {
        // Convert :param to {param}
        return preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $path);
    }

    /**
     * Get all registered routes
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Generate URL for route
     *
     * @param string $name Route name
     * @param array<string, mixed> $params Parameters
     * @return string|null
     */
    public function generate(string $name, array $params = []): ?string
    {
        if (!isset($this->routes[$name])) {
            return null;
        }

        $path = $this->routes[$name]['path'];

        // Replace :param with actual values
        foreach ($params as $key => $value) {
            $path = str_replace(':' . $key, (string)$value, $path);
        }

        return $path;
    }
}
