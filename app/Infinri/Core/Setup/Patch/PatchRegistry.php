<?php
declare(strict_types=1);

namespace Infinri\Core\Setup\Patch;

/**
 * Discovers and manages data patches from all modules
 */
class PatchRegistry
{
    private array $patches = [];

    /**
     * Discover patches from all modules
     */
    public function discoverPatches(): void
    {
        $appDir = __DIR__ . '/../../../../';
        $modules = $this->findModules($appDir);

        foreach ($modules as $module) {
            $patchDir = $appDir . $module . '/Setup/Patch/Data';

            if (!is_dir($patchDir)) {
                continue;
            }

            $this->scanDirectory($patchDir, $module);
        }
    }

    /**
     * Find all modules in app/Infinri
     */
    private function findModules(string $appDir): array
    {
        $modules = [];
        $infinriDir = $appDir . 'Infinri';

        if (!is_dir($infinriDir)) {
            return $modules;
        }

        foreach (scandir($infinriDir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $infinriDir . '/' . $item;
            if (is_dir($path)) {
                $modules[] = 'Infinri/' . $item;
            }
        }

        return $modules;
    }

    /**
     * Scan directory for patch files
     */
    private function scanDirectory(string $dir, string $module): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..' || !str_ends_with($file, '.php')) {
                continue;
            }

            $className = str_replace('/', '\\', $module)
                . '\\Setup\\Patch\\Data\\'
                . basename($file, '.php');

            if (class_exists($className) && is_subclass_of($className, DataPatchInterface::class)) {
                $this->patches[] = $className;
            }
        }
    }

    /**
     * Get all discovered patches
     */
    public function getPatches(): array
    {
        return $this->patches;
    }

    /**
     * Sort patches by dependencies
     */
    public function sortByDependencies(array $patches): array
    {
        $sorted = [];
        $visited = [];

        foreach ($patches as $patch) {
            $this->visitPatch($patch, $patches, $sorted, $visited);
        }

        return $sorted;
    }

    /**
     * Visit patch and its dependencies (topological sort)
     */
    private function visitPatch(string $patch, array $allPatches, array &$sorted, array &$visited): void
    {
        if (isset($visited[$patch])) {
            return;
        }

        $visited[$patch] = true;

        // Visit dependencies first
        $dependencies = $patch::getDependencies();
        foreach ($dependencies as $dependency) {
            if (in_array($dependency, $allPatches, true)) {
                $this->visitPatch($dependency, $allPatches, $sorted, $visited);
            }
        }

        $sorted[] = $patch;
    }
}
