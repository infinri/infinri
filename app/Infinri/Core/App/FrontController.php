<?php
declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Core\Helper\Logger;
use Infinri\Core\App\Middleware\SecurityHeadersMiddleware;

/**
 * Front Controller
 * 
 * Entry point for all HTTP requests. Orchestrates routing and dispatching.
 * 
 * Phase 3.1: SOLID Refactoring - Slimmed down to orchestration only
 * - Router handles route matching
 * - Dispatcher handles controller instantiation and execution
 * - FrontController orchestrates the flow
 */
class FrontController
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Dispatcher $dispatcher,
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
            
            Logger::debug('Attempting to match route', ['uri' => $uri, 'method' => $method]);
            
            // Check for redirects first (highest priority)
            if ($redirectResponse = $this->handleRedirect($uri, $request, $response)) {
                return $redirectResponse;
            }
            
            // Check URL rewrites before routing
            if ($rewriteResponse = $this->handleUrlRewrite($uri, $request, $response)) {
                return $rewriteResponse;
            }
            
            // Match and dispatch route
            return $this->matchAndDispatchRoute($uri, $method, $request, $response);

        } catch (\Throwable $e) {
            // Log exception
            Logger::exception($e, 'Exception during request dispatch');
            
            // Handle errors
            $response->setServerError()->setBody($this->formatError($e));
            return $this->securityHeaders->handle($request, $response);
        }
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

    /**
     * Handle redirect if exists
     */
    private function handleRedirect(string $uri, Request $request, Response $response): ?Response
    {
        $redirect = $this->checkRedirect($uri);
        if (!$redirect) {
            return null;
        }
        
        Logger::info('Redirect applied', [
            'from' => $uri,
            'to' => $redirect['to_path'],
            'code' => $redirect['redirect_code']
        ]);
        
        $response->setStatusCode($redirect['redirect_code']);
        $response->setHeader('Location', '/' . $redirect['to_path']);
        return $this->securityHeaders->handle($request, $response);
    }

    /**
     * Handle URL rewrite if exists
     */
    private function handleUrlRewrite(string &$uri, Request $request, Response $response): ?Response
    {
        $urlRewrite = $this->checkUrlRewrite($uri);
        if (!$urlRewrite) {
            return null;
        }
        
        // Handle redirect types (301/302)
        if ($urlRewrite['redirect_type'] > 0) {
            Logger::info('URL rewrite redirect', [
                'from' => $uri,
                'to' => $urlRewrite['target_path'],
                'code' => $urlRewrite['redirect_type']
            ]);
            
            $response->setStatusCode($urlRewrite['redirect_type']);
            $response->setHeader('Location', '/' . $urlRewrite['target_path']);
            return $this->securityHeaders->handle($request, $response);
        }
        
        // Internal rewrite (no redirect)
        Logger::info('URL rewrite applied', ['from' => $uri, 'to' => $urlRewrite['target_path']]);
        $uri = $this->parseTargetPath($urlRewrite['target_path'], $request);
        
        return null;
    }

    /**
     * Match route and dispatch to controller
     */
    private function matchAndDispatchRoute(string $uri, string $method, Request $request, Response $response): Response
    {
        $match = $this->router->match($uri, $method);

        if ($match === null) {
            Logger::warning('404 - Route not found', ['uri' => $uri, 'method' => $method]);
            $response->setNotFound()->setBody('404 - Page Not Found');
            return $this->securityHeaders->handle($request, $response);
        }
        
        $route = new Route(
            controller: $match['controller'],
            action: $match['action'],
            params: $match['params']
        );
        
        Logger::info('Route matched successfully', [
            'uri' => $uri,
            'controller' => $route->controller,
            'action' => $route->action
        ]);

        // Check authentication for admin routes
        if (str_starts_with($uri, '/admin')) {
            $response = $this->authMiddleware->handle($request, $response);
            if ($response->getStatusCode() !== 200) {
                return $this->securityHeaders->handle($request, $response);
            }
        }

        $response = $this->dispatcher->dispatch($route);
        return $this->securityHeaders->handle($request, $response);
    }

    /**
     * Check if request path has a redirect rule
     *
     * @param string $uri Request path
     * @return array|null Redirect data or null
     */
    private function checkRedirect(string $uri): ?array
    {
        // Skip redirect check for admin, static, media, and special files
        if (str_starts_with($uri, '/admin') || 
            str_starts_with($uri, '/static') || 
            str_starts_with($uri, '/media') ||
            str_ends_with($uri, '.xml') ||
            str_ends_with($uri, '.txt')) {
            return null;
        }

        try {
            // Check if SEO module classes exist
            if (!class_exists(\Infinri\Seo\Model\ResourceModel\Redirect::class)) {
                return null;
            }

            // Manually build dependency chain
            $connection = new \Infinri\Core\Model\ResourceModel\Connection();
            $redirectResource = new \Infinri\Seo\Model\ResourceModel\Redirect($connection);
            
            // Normalize path and find redirect
            $normalizedPath = trim($uri, '/');
            return $redirectResource->findByFromPath($normalizedPath);
        } catch (\Throwable $e) {
            // If SEO module not available or error, continue without redirect
            Logger::debug('Redirect check failed', [
                'uri' => $uri,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if request path has a URL rewrite
     *
     * @param string $uri Request path
     * @return array|null URL rewrite data or null
     */
    private function checkUrlRewrite(string $uri): ?array
    {
        // Skip URL rewrite check for admin, static, media, and special files
        if (str_starts_with($uri, '/admin') || 
            str_starts_with($uri, '/static') || 
            str_starts_with($uri, '/media') ||
            str_ends_with($uri, '.xml') ||
            str_ends_with($uri, '.txt')) {
            return null;
        }

        try {
            // Check if SEO module classes exist
            if (!class_exists(\Infinri\Seo\Service\UrlRewriteResolver::class)) {
                return null;
            }

            // Manually build dependency chain (ObjectManager struggles with optional modules)
            $connection = new \Infinri\Core\Model\ResourceModel\Connection();
            $urlRewriteResource = new \Infinri\Seo\Model\ResourceModel\UrlRewrite($connection);
            $urlRewriteRepository = new \Infinri\Seo\Model\Repository\UrlRewriteRepository($urlRewriteResource);
            $resolver = new \Infinri\Seo\Service\UrlRewriteResolver($urlRewriteRepository);
            
            return $resolver->resolve($uri);
        } catch (\Throwable $e) {
            // If SEO module not available or error, continue without rewrite
            Logger::debug('URL rewrite check failed', [
                'uri' => $uri,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse target path and extract query parameters
     *
     * @param string $targetPath Target path from URL rewrite (e.g., "cms/page/view?key=test")
     * @param Request $request Request object to add parameters to
     * @return string Clean path without query string
     */
    private function parseTargetPath(string $targetPath, Request $request): string
    {
        // Split path and query string
        $parts = explode('?', $targetPath, 2);
        $path = $parts[0];
        
        // Parse query string parameters and add to request
        if (isset($parts[1])) {
            parse_str($parts[1], $params);
            foreach ($params as $key => $value) {
                $request->setParam($key, $value);
            }
        }
        
        // Return clean path for routing
        return '/' . ltrim($path, '/');
    }
}
