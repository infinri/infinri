<?php

declare(strict_types=1);

namespace Infinri\Core\Console;

use Symfony\Component\Console\Command\Command;
use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Api\ComponentRegistrarInterface;
use Infinri\Core\Console\Command\CacheClearCommand;
use Infinri\Core\Console\Command\CacheStatusCommand;
use Infinri\Core\Console\Command\ModuleListCommand;
use Infinri\Core\Console\Command\ModuleStatusCommand;
use Infinri\Core\Console\Command\SetupUpgradeCommand;

/**
 * Discovers and loads console commands from all modules
 */
class CommandLoader
{
    /**
     * Component Registrar
     *
     * @var ComponentRegistrarInterface
     */
    private ComponentRegistrarInterface $componentRegistrar;

    /**
     * Registered commands
     *
     * @var array<Command>
     */
    private array $commands = [];

    /**
     * Constructor
     *
     * @param ComponentRegistrarInterface|null $componentRegistrar
     */
    public function __construct(?ComponentRegistrarInterface $componentRegistrar = null)
    {
        $this->componentRegistrar = $componentRegistrar ?? ComponentRegistrar::getInstance();
    }

    /**
     * Load all commands from modules
     *
     * @return array<Command> Array of command instances
     */
    public function loadCommands(): array
    {
        // Load core commands
        $this->loadCoreCommands();

        // Load commands from modules (future: discover from modules)
        $this->discoverModuleCommands();

        return $this->commands;
    }

    /**
     * Load core framework commands
     *
     * @return void
     */
    private function loadCoreCommands(): void
    {
        $coreCommandClasses = [
            CacheClearCommand::class,
            CacheStatusCommand::class,
            ModuleListCommand::class,
            ModuleStatusCommand::class,
            SetupUpgradeCommand::class,
        ];

        foreach ($coreCommandClasses as $commandClass) {
            if (class_exists($commandClass)) {
                $this->commands[] = new $commandClass();
            }
        }
    }

    /**
     * Discover commands from modules
     *
     * @return void
     */
    private function discoverModuleCommands(): void
    {
        $modules = $this->componentRegistrar->getPaths(ComponentRegistrarInterface::MODULE);

        foreach ($modules as $moduleName => $modulePath) {
            $commandPath = $modulePath . '/Console/Command';

            if (!is_dir($commandPath)) {
                continue;
            }

            // Scan for command files
            $files = glob($commandPath . '/*Command.php');

            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file, $moduleName);

                if ($className && class_exists($className)) {
                    $command = new $className();
                    if ($command instanceof Command) {
                        $this->commands[] = $command;
                    }
                }
            }
        }
    }

    /**
     * Get class name from file path
     *
     * @param string $file File path
     * @param string $moduleName Module name
     * @return string|null Class name or null
     */
    private function getClassNameFromFile(string $file, string $moduleName): ?string
    {
        $basename = basename($file, '.php');
        $namespace = str_replace('_', '\\', $moduleName);

        return $namespace . '\\Console\\Command\\' . $basename;
    }

    /**
     * Get loaded commands
     *
     * @return array<Command> Commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Register a command manually
     *
     * @param Command $command Command instance
     * @return void
     */
    public function registerCommand(Command $command): void
    {
        $this->commands[] = $command;
    }
}
