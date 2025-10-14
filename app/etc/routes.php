<?php
/**
 * Application Routes Configuration
 */

declare(strict_types=1);

use Infinri\Core\App\Router;

return function (Router $router) {
    // Homepage
    $router->addRoute(
        'home',
        '/',
        \Infinri\Core\Controller\Index\IndexController::class,
        'execute',
        ['GET']
    );
    
    // About page
    $router->addRoute(
        'about',
        '/about',
        \Infinri\Core\Controller\Page\AboutController::class,
        'execute',
        ['GET']
    );
    
    // Product view with ID parameter
    $router->addRoute(
        'product_view',
        '/product/:id',
        \Infinri\Core\Controller\Product\ViewController::class,
        'execute',
        ['GET']
    );
    
    // JSON API example
    $router->addRoute(
        'api_test',
        '/api/test',
        \Infinri\Core\Controller\Api\TestController::class,
        'execute',
        ['GET', 'POST']
    );
};
