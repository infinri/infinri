<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Module;

use Infinri\Core\Model\ComponentRegistrar;

/**
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
     * Get all registered modules with their data.
     *
     * @return array<string, array<string, mixed>> Array of module_name => module_data
     */
    public function getAll(): array
    {
        if (null === $this->modules) {
            $this->load();
        }

        return $this->modules ?? [];
    }

    /**
     * Get all module names.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->getAll());
    }

    /**
     * Get single module data.
     *
     * @return array<string, mixed>|null
     */
    public function getOne(string $moduleName): ?array
    {
        $modules = $this->getAll();

        return $modules[$moduleName] ?? null;
    }

    /**
     * Check if module exists.
     */
    public function has(string $moduleName): bool
    {
        return isset($this->getAll()[$moduleName]);
    }

    /**
     * Load modules from ComponentRegistrar and parse their module.xml files.
     */
    private function load(): void
    {
        $this->modules = [];

        $modulePaths = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);

        foreach ($modulePaths as $moduleName => $modulePath) {
            $moduleData = $this->moduleReader->read($modulePath);

            if (null !== $moduleData) {
                $this->modules[$moduleName] = array_merge(
                    $moduleData,
                    ['path' => $modulePath]
                );
            }
        }
    }

    /**
     * Clear cached module data.
     */
    public function clearCache(): void
    {
        $this->modules = null;
    }
}
