<?php

/**
 * Bootstrap Application
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
use Infinri\Core\App\Middleware\SecurityHeadersMiddleware;
use Infinri\Core\App\Middleware\AuthenticationMiddleware;
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
 * @throws RuntimeException
 * @throws InvalidArgumentException
 * @throws Psr\Cache\InvalidArgumentException
 * @throws Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface
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

    // Enable compilation in production for 100-200ms faster bootstrap
    $useCache = ($_ENV['APP_ENV'] ?? 'development') === 'production';
    $container = $containerFactory->create($useCache);

    // 4. Initialize ObjectManager (DI facade)
    ObjectManager::reset();
    $objectManager = ObjectManager::setInstance($container);

    // 5. Initialize FastRouter (O(1) routing performance)
    $router = new FastRouter();

    // 5.1 Register SEO-specific routes FIRST (sitemap.xml, robots.txt)
    // These must be registered before CMS catch-all route for proper precedence
    $seoRoutesFile = __DIR__ . '/Infinri/Seo/Setup/RegisterSeoRoutes.php';
    if (file_exists($seoRoutesFile)) {
        require_once $seoRoutesFile;
        \Infinri\Seo\Setup\RegisterSeoRoutes::register($router);
    }

    // 6. Load routes from modules' routes.xml files (automatic discovery)
    $routeLoader = new RouteLoader($moduleManager);
    $routeLoader->loadRoutes($router);

    // 7. Create Front Controller with Dispatcher (Phase 3.1 SOLID Refactoring)
    $objectManager = ObjectManager::getInstance();
    $request = Request::createFromGlobals();
    $dispatcher = new \Infinri\Core\App\Dispatcher($objectManager, $request);

    return new FrontController(
        $router,
        $dispatcher,
        new SecurityHeadersMiddleware(),
        $objectManager->get(AuthenticationMiddleware::class)
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
