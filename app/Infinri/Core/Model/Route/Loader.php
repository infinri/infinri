<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Route;

use Infinri\Core\App\RouterInterface;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Helper\Logger;

/**
 * Route Loader
 * 
 * Automatically discovers and loads routes from routes.xml files in enabled modules
 */
class Loader
{
    public function __construct(
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * Load routes from all enabled modules' routes.xml files
     *
     * @param RouterInterface $router
     * @return void
     */
    public function loadRoutes(RouterInterface $router): void
    {
        // Get all registered modules with full data
        $allModules = $this->moduleManager->getModuleList()->getAll();
        
        $enabledCount = 0;
        
        Logger::info('RouteLoader: Starting route discovery', [
            'total_modules' => count($allModules)
        ]);
        
        foreach ($allModules as $moduleName => $moduleData) {
            // Skip disabled modules
            if (!$this->moduleManager->isEnabled($moduleName)) {
                Logger::debug("RouteLoader: Skipping disabled module {$moduleName}");
                continue;
            }
            
            $enabledCount++;
            $this->loadModuleRoutes($router, $moduleName, $moduleData);
        }
        
        Logger::info('RouteLoader: Route discovery complete', [
            'enabled_modules' => $enabledCount,
            'total_routes' => count($router->getRoutes())
        ]);
    }

    /**
     * Load routes from a single module's routes.xml
     *
     * @param RouterInterface $router
     * @param string $moduleName
     * @param array $moduleData
     * @return void
     */
    private function loadModuleRoutes(RouterInterface $router, string $moduleName, array $moduleData): void
    {
        // Check for both frontend and adminhtml routes.xml files
        $routePaths = [
            'frontend' => $moduleData['path'] . '/etc/routes.xml',
            'adminhtml' => $moduleData['path'] . '/etc/adminhtml/routes.xml',
        ];
        
        $foundRoutes = false;
        
        foreach ($routePaths as $area => $routesXmlPath) {
            if (!file_exists($routesXmlPath)) {
                Logger::debug("RouteLoader: No {$area} routes.xml for {$moduleName}");
                continue;
            }
            
            try {
                $xml = simplexml_load_file($routesXmlPath);
                
                if ($xml === false) {
                    Logger::warning("RouteLoader: Failed to parse {$area} routes.xml for {$moduleName}");
                    continue;
                }
                
                $this->parseRoutesXml($router, $moduleName, $xml, $moduleData);
                $foundRoutes = true;
                
            } catch (\Exception $e) {
                Logger::exception($e, "RouteLoader: Error loading {$area} routes from {$moduleName}");
            }
        }
        
        if (!$foundRoutes) {
            Logger::debug("RouteLoader: No routes.xml files found for {$moduleName}");
        }
    }

    /**
     * Parse routes.xml and register routes
     *
     * @param RouterInterface $router
     * @param string $moduleName
     * @param \SimpleXMLElement $xml
     * @param array $moduleData
     * @return void
     */
    private function parseRoutesXml(RouterInterface $router, string $moduleName, \SimpleXMLElement $xml, array $moduleData): void
    {
        // Parse routes from all routers (frontend, admin, etc.)
        // Process admin routes FIRST to ensure they take priority over catch-all routes
        if (isset($xml->router)) {
            $routers = [];
            foreach ($xml->router as $routerNode) {
                $routerId = (string)($routerNode['id'] ?? 'frontend');
                $routers[$routerId] = $routerNode;
            }
            
            // Process in priority order: admin first, then frontend
            $order = ['admin', 'frontend'];
            foreach ($order as $routerId) {
                if (isset($routers[$routerId])) {
                    Logger::debug("RouteLoader: Processing {$routerId} routes for {$moduleName}");
                    
                    foreach ($routers[$routerId]->route as $routeNode) {
                        $this->registerRoute($router, $moduleName, $routeNode, $routerId);
                    }
                }
            }
            
            // Process any other router types not in the priority list
            foreach ($routers as $routerId => $routerNode) {
                if (!in_array($routerId, $order)) {
                    Logger::debug("RouteLoader: Processing {$routerId} routes for {$moduleName}");
                    
                    foreach ($routerNode->route as $routeNode) {
                        $this->registerRoute($router, $moduleName, $routeNode, $routerId);
                    }
                }
            }
        }
    }

    /**
     * Register a single route from XML configuration
     *
     * @param RouterInterface $router
     * @param string $moduleName
     * @param \SimpleXMLElement $routeNode
     * @param string $routerId Router type (frontend, admin, etc.)
     * @return void
     */
    private function registerRoute(RouterInterface $router, string $moduleName, \SimpleXMLElement $routeNode, string $routerId = 'frontend'): void
    {
        $routeId = (string)($routeNode['id'] ?? '');
        $frontName = (string)($routeNode['frontName'] ?? '');
        
        if (empty($routeId) || empty($frontName)) {
            Logger::warning("RouteLoader: Invalid route configuration in {$moduleName}");
            return;
        }
        
        // Convert module name to controller namespace
        // Infinri_Cms → Infinri\Cms\Controller
        $baseNamespace = str_replace('_', '\\', $moduleName) . '\\Controller';
        
        // For admin routes, add Adminhtml to namespace
        $namespace = ($routerId === 'admin') 
            ? $baseNamespace . '\\Adminhtml' 
            : $baseNamespace;

        // Register routes for this frontName
        // Magento-style: frontName maps to module controllers
        
        // Special handling for CMS module - catch-all routing for frontend only
        if ($frontName === 'cms' && $routerId === 'frontend') {
            $router->addRoute(
                'cms_index_index',
                '/',
                $namespace . '\\Index\\Index',
                'execute',
                ['GET']
            );
            
            // Catch-all for CMS pages by URL key (frontend only, not admin)
            // Use single-level pattern to avoid matching admin URLs like /admin/cms/...
            $router->addRoute(
                'cms_page_view',
                '/:urlkey',
                $namespace . '\\Page\\View',
                'execute',
                ['GET']
            );
            
            Logger::info("RouteLoader: Registered CMS catch-all routes", [
                'module' => $moduleName,
                'routes' => ['/', '/*']
            ]);
        } else {
            // Standard module route: /{routerId}/{frontName}/{controller}/{action}
            // Admin routes: /admin/{frontName}/...
            // Frontend routes: /{frontName}/...
            $prefix = ($routerId === 'admin') ? '/admin' : '';
            
            $router->addRoute(
                "{$routeId}_index_index",
                "{$prefix}/{$frontName}",
                $namespace . '\\Index\\Index',
                'execute',
                ['GET', 'POST']
            );
            
            $router->addRoute(
                "{$routeId}_controller_action",
                "{$prefix}/{$frontName}/:controller/:action",
                $namespace . '\\:controller\\:action',
                'execute',
                ['GET', 'POST']
            );
            
            Logger::info("RouteLoader: Registered standard routes", [
                'module' => $moduleName,
                'routerId' => $routerId,
                'frontName' => $frontName,
                'pattern' => "{$prefix}/{$frontName}/*"
            ]);
        }
    }
}
