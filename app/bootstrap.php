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
use Infinri\Core\Model\Route\Loader as RouteLoader;
use Infinri\Core\App\Request;

/**
 * Load environment variables from .env file
 * 
 * @param string $envFile Path to .env file
 * @return void
 */
function loadEnvFile(string $envFile): void
{
    if (!file_exists($envFile)) {
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes from value
            $value = trim($value, '"\'');
            
            // Set environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load environment variables from .env file
loadEnvFile(__DIR__ . '/../.env');

// Require Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file
loadEnvFile(__DIR__ . '/../.env');

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
    
    // 5. Initialize Router
    $router = new Router();
    
    // 6. Load routes from modules' routes.xml files (automatic discovery)
    $routeLoader = new RouteLoader($moduleManager);
    $routeLoader->loadRoutes($router);
    
    // 7. Create Request object from globals
    $request = Request::createFromGlobals();
    
    // 8. Create Front Controller with singleton ObjectManager instance
    return new FrontController(
        $router, 
        ObjectManager::getInstance(),
        $request
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
