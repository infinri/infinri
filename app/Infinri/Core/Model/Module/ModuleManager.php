<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Module;

/**
 * Manages module enabled/disabled status and dependency order.
 * Reads from app/etc/config.php to determine which modules are enabled.
 */
class ModuleManager
{
    /**
     * @var array<string, int>|null Cached enabled modules list
     */
    private ?array $enabledModules = null;

    /**
     * @var string Path to config.php
     */
    private string $configPath;

    public function __construct(
        private readonly ModuleList $moduleList,
        ?string $configPath = null
    ) {
        $this->configPath = $configPath ?? __DIR__ . '/../../../../etc/config.php';
    }

    /**
     * Check if a module is enabled.
     */
    public function isEnabled(string $moduleName): bool
    {
        $enabledModules = $this->getEnabledModules();

        return isset($enabledModules[$moduleName]) && 1 === $enabledModules[$moduleName];
    }

    /**
     * Get all enabled modules.
     *
     * @return array<string, int> Array of module_name => 1 (enabled) or 0 (disabled)
     */
    public function getEnabledModules(): array
    {
        if (null === $this->enabledModules) {
            $this->loadEnabledModules();
        }

        return $this->enabledModules ?? [];
    }

    /**
     * Get list of enabled module names only.
     *
     * @return string[]
     */
    public function getEnabledModuleNames(): array
    {
        $enabled = [];

        foreach ($this->getEnabledModules() as $moduleName => $status) {
            if (1 === $status) {
                $enabled[] = $moduleName;
            }
        }

        return $enabled;
    }

    /**
     * Get modules in dependency order (respecting module sequence).
     *
     * @return string[] Module names sorted by dependency order
     */
    public function getModulesInOrder(): array
    {
        $allModules = $this->moduleList->getAll();
        $enabledNames = $this->getEnabledModuleNames();

        // Filter to only enabled modules
        $modules = array_filter(
            $allModules,
            fn ($name) => \in_array($name, $enabledNames, true),
            \ARRAY_FILTER_USE_KEY
        );

        return $this->sortByDependency($modules);
    }

    /**
     * Load enabled modules from app/etc/config.php.
     */
    private function loadEnabledModules(): void
    {
        $this->enabledModules = [];

        if (! file_exists($this->configPath)) {
            // No config file, enable all registered modules by default
            foreach ($this->moduleList->getNames() as $moduleName) {
                $this->enabledModules[$moduleName] = 1;
            }

            return;
        }

        $config = include $this->configPath;

        if (! isset($config['modules']) || ! \is_array($config['modules'])) {
            // Invalid config structure, enable all by default
            foreach ($this->moduleList->getNames() as $moduleName) {
                $this->enabledModules[$moduleName] = 1;
            }

            return;
        }

        $this->enabledModules = $config['modules'];
    }

    /**
     * Sort modules by dependency order (topological sort).
     *
     * @param array<string, array<string, mixed>> $modules
     *
     * @return string[]
     */
    private function sortByDependency(array $modules): array
    {
        $sorted = [];
        $visited = [];

        foreach (array_keys($modules) as $moduleName) {
            $this->visitModule($moduleName, $modules, $visited, $sorted);
        }

        return $sorted;
    }

    /**
     * Visit module for topological sort (depth-first).
     *
     * @param array<string, array<string, mixed>> $modules
     * @param array<string, bool>                 $visited
     * @param string[]                            $sorted
     */
    private function visitModule(
        string $moduleName,
        array $modules,
        array &$visited,
        array &$sorted
    ): void {
        // Already visited
        if (isset($visited[$moduleName])) {
            return;
        }

        // Module not in list (might be disabled dependency)
        if (! isset($modules[$moduleName])) {
            return;
        }

        // Mark as being visited (for circular dependency detection)
        $visited[$moduleName] = true;

        // Visit dependencies first
        $dependencies = $modules[$moduleName]['sequence'] ?? [];
        foreach ($dependencies as $dependency) {
            $this->visitModule($dependency, $modules, $visited, $sorted);
        }

        // Add to sorted list
        $sorted[] = $moduleName;
    }

    /**
     * Get ModuleList instance.
     */
    public function getModuleList(): ModuleList
    {
        return $this->moduleList;
    }

    /**
     * Clear cached data.
     */
    public function clearCache(): void
    {
        $this->enabledModules = null;
    }
}
