<?php
/**
 * Bootstrap Application
 * 
 * Initialize all components: modules, config, DI container, router
 */

declare(strict_types=1);

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Di\XmlReader;
use Infinri\Core\Model\Di\ContainerFactory;
use Infinri\Core\Model\ObjectManager;
use Infinri\Core\App\Router;
use Infinri\Core\App\FrontController;

// Require Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Initialize Application
 * 
 * @return FrontController
 */
function initApplication(): FrontController
{
    // 1. Register all modules
    require __DIR__ . '/etc/registration_globlist.php';
    
    // 2. Initialize Module System
    $registrar = ComponentRegistrar::getInstance();
    $moduleReader = new ModuleReader();
    $moduleList = new ModuleList($registrar, $moduleReader);
    $moduleManager = new ModuleManager($moduleList);
    
    // 3. Build DI Container
    $xmlReader = new XmlReader();
    $containerFactory = new ContainerFactory($moduleManager, $xmlReader);
    $container = $containerFactory->create();
    
    // 4. Initialize ObjectManager (DI facade)
    ObjectManager::reset();
    $objectManager = ObjectManager::setInstance($container);
    
    // 5. Initialize Router
    $router = new Router();
    
    // Load routes from configuration
    if (file_exists(__DIR__ . '/etc/routes.php')) {
        $routes = require __DIR__ . '/etc/routes.php';
        if (is_callable($routes)) {
            $routes($router);
        }
    }
    
    // 6. Create Front Controller
    return new FrontController($router, $objectManager);
}

/**
 * Get ObjectManager instance
 * 
 * @return ObjectManager
 */
function getObjectManager(): ObjectManager
{
    return ObjectManager::getInstance();
}
