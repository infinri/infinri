<?php
declare(strict_types=1);

namespace Infinri\Core\App;

/**
 * Common interface for all router implementations
 */
interface RouterInterface
{
    /**
     * Register a route
     *
     * @param string $name Route name
     * @param string $path URL pattern (e.g., '/product/view/:id')
     * @param string $controller Controller class
     * @param string $action Action method
     * @param array<string> $methods Allowed HTTP methods
     * @return $this
     */
    public function addRoute(
        string $name,
        string $path,
        string $controller,
        string $action = 'execute',
        array  $methods = ['GET', 'POST']
    ): self;

    /**
     * Match URL to route
     *
     * @param string $path Request path
     * @param string $method HTTP method
     * @return array|null ['controller' => ..., 'action' => ..., 'params' => [...]] or null
     */
    public function match(string $path, string $method = 'GET'): ?array;

    /**
     * Get all registered routes
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRoutes(): array;

    /**
     * Generate URL for route
     *
     * @param string $name Route name
     * @param array<string, mixed> $params Parameters
     * @return string|null
     */
    public function generate(string $name, array $params = []): ?string;
}
