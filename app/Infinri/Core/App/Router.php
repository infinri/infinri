<?php
declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * Registers routes and matches URLs to controllers/actions
 */
class Router implements RouterInterface
{
    /**
     * @var array<string, array<string, mixed>> Registered routes
     */
    private array $routes = [];

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
            'pattern' => $this->convertToRegex($path),
            'specificity' => $this->calculateSpecificity($path),
        ];

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
        // Sort routes by specificity (most specific first)
        $sortedRoutes = $this->routes;
        uasort($sortedRoutes, function ($a, $b) {
            return ($b['specificity'] ?? 0) <=> ($a['specificity'] ?? 0);
        });

        foreach ($sortedRoutes as $name => $route) {
            // Check if method is allowed
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            // Try to match pattern
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                return [
                    'name' => $name,
                    'controller' => $route['controller'],
                    'action' => $route['action'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    /**
     * Convert route path to regex pattern
     *
     * @param string $path
     * @return string
     */
    private function convertToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $path);

        // Convert :param to named capture group
        $pattern = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '(?P<$1>[^\/]+)', $pattern);

        // Convert * to wildcard
        $pattern = str_replace('*', '.*', $pattern);

        return '/^' . $pattern . '$/';
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
