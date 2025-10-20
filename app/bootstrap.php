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
use Infinri\Core\App\FastRouter;
use Infinri\Core\App\FrontController;
use Infinri\Core\Model\Route\Loader as RouteLoader;
use Infinri\Core\App\Request;
use Dotenv\Dotenv;

// Require Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file using phpdotenv
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad(); // safeLoad() won't throw if .env is missing

/**
 * Initialize Application
 *
 * @return FrontController
 * @throws Exception
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
    
    // 5. Initialize FastRouter (O(1) routing performance)
    $router = new FastRouter();
    
    // 6. Load routes from modules' routes.xml files (automatic discovery)
    $routeLoader = new RouteLoader($moduleManager);
    $routeLoader->loadRoutes($router);
    
    // 7. Create Front Controller with singleton ObjectManager instance
    return new FrontController(
        $router, 
        ObjectManager::getInstance(),
        Request::createFromGlobals()
    );
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
