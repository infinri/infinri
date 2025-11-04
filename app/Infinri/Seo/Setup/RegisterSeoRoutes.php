<?php

declare(strict_types=1);

namespace Infinri\Seo\Setup;

use Infinri\Core\App\RouterInterface;

/**
 * Registers sitemap.xml and robots.txt routes directly.
 */
class RegisterSeoRoutes
{
    /**
     * Register SEO routes.
     */
    public static function register(RouterInterface $router): void
    {
        // Register sitemap.xml route
        $router->addRoute(
            'seo_sitemap',                              // Route name
            '/sitemap.xml',                             // Path
            'Infinri\Seo\Controller\Sitemap\Index',     // Controller
            'execute',                                  // Action
            ['GET']                                     // Methods
        );

        // Register robots.txt route
        $router->addRoute(
            'seo_robots',                               // Route name
            '/robots.txt',                              // Path
            'Infinri\Seo\Controller\Robots\Index',      // Controller
            'execute',                                  // Action
            ['GET']                                     // Methods
        );
    }
}
