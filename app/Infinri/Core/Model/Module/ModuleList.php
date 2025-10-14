<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Module;

use Infinri\Core\Model\ComponentRegistrar;

/**
 * Module List
 * 
 * Provides access to registered modules and their information.
 */
class ModuleList
{
    /**
     * @var array<string, array<string, mixed>>|null Cached module data
     */
    private ?array $modules = null;

    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly ModuleReader $moduleReader
    ) {
    }

    /**
     * Get all registered modules with their data
     *
     * @return array<string, array<string, mixed>> Array of module_name => module_data
     */
    public function getAll(): array
    {
        if ($this->modules === null) {
            $this->load();
        }

        return $this->modules;
    }

    /**
     * Get all module names
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->getAll());
    }

    /**
     * Get single module data
     *
     * @param string $moduleName
     * @return array<string, mixed>|null
     */
    public function getOne(string $moduleName): ?array
    {
        $modules = $this->getAll();
        return $modules[$moduleName] ?? null;
    }

    /**
     * Check if module exists
     *
     * @param string $moduleName
     * @return bool
     */
    public function has(string $moduleName): bool
    {
        return isset($this->getAll()[$moduleName]);
    }

    /**
     * Load modules from ComponentRegistrar and parse their module.xml files
     *
     * @return void
     */
    private function load(): void
    {
        $this->modules = [];
        
        $modulePaths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);

        foreach ($modulePaths as $moduleName => $modulePath) {
            $moduleData = $this->moduleReader->read($modulePath);
            
            if ($moduleData !== null) {
                $this->modules[$moduleName] = array_merge(
                    $moduleData,
                    ['path' => $modulePath]
                );
            }
        }
    }

    /**
     * Clear cached module data
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->modules = null;
    }
}
