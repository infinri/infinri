<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Asset;

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Api\ComponentRegistrarInterface;

/**
 * Asset Publisher
 * 
 * Publishes assets from module view/{area}/web/ directories to pub/static/
 * Supports both symlinks (development) and file copying (production)
 */
class Publisher
{
    /**
     * Public static directory
     */
    private const STATIC_DIR = '/pub/static';

    /**
     * Component Registrar
     *
     * @var ComponentRegistrarInterface
     */
    private ComponentRegistrarInterface $componentRegistrar;

    /**
     * Base application path
     *
     * @var string
     */
    private string $basePath;

    /**
     * Use symlinks instead of copying (development mode)
     *
     * @var bool
     */
    private bool $useSymlinks;

    /**
     * Constructor
     *
     * @param string|null $basePath Application base path
     * @param ComponentRegistrarInterface|null $componentRegistrar
     * @param bool $useSymlinks Use symlinks (dev) or copy files (prod)
     */
    public function __construct(
        ?string $basePath = null,
        ?ComponentRegistrarInterface $componentRegistrar = null,
        bool $useSymlinks = true
    ) {
        $this->basePath = $basePath ?? dirname(__DIR__, 5); // Default to project root
        $this->componentRegistrar = $componentRegistrar ?? ComponentRegistrar::getInstance();
        $this->useSymlinks = $useSymlinks;
    }

    /**
     * Publish assets for a module
     *
     * @param string $moduleName Module name (e.g., 'Infinri_Core')
     * @param string $area Area (frontend, adminhtml, base)
     * @return bool True on success
     */
    public function publish(string $moduleName, string $area = 'frontend'): bool
    {
        $modulePath = $this->componentRegistrar->getPath(
            ComponentRegistrarInterface::MODULE,
            $moduleName
        );

        if ($modulePath === null) {
            throw new \RuntimeException("Module {$moduleName} not found");
        }

        // Source: app/Infinri/Module/view/area/web/
        $sourcePath = $modulePath . '/view/' . $area . '/web';

        if (!is_dir($sourcePath)) {
            // No assets to publish
            return true;
        }

        // Target: pub/static/Infinri/Module/
        $modulePathSegment = str_replace('_', '/', $moduleName);
        $targetPath = $this->basePath . self::STATIC_DIR . '/' . $modulePathSegment;

        // Create target directory
        if (!is_dir($targetPath)) {
            if (!mkdir($targetPath, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$targetPath}");
            }
        }

        // Publish assets
        if ($this->useSymlinks) {
            return $this->createSymlink($sourcePath, $targetPath);
        } else {
            return $this->copyDirectory($sourcePath, $targetPath);
        }
    }

    /**
     * Publish assets for all modules
     *
     * @param string $area Area to publish
     * @return array Array of results [moduleName => bool]
     */
    public function publishAll(string $area = 'frontend'): array
    {
        $modules = $this->componentRegistrar->getPaths(ComponentRegistrarInterface::MODULE);
        $results = [];

        foreach ($modules as $moduleName => $modulePath) {
            try {
                $results[$moduleName] = $this->publish($moduleName, $area);
            } catch (\RuntimeException $e) {
                $results[$moduleName] = false;
            }
        }

        return $results;
    }

    /**
     * Create symlink from source to target
     *
     * @param string $source Source directory
     * @param string $target Target directory
     * @return bool True on success
     */
    private function createSymlink(string $source, string $target): bool
    {
        // Remove existing symlink or directory
        if (file_exists($target)) {
            if (is_link($target)) {
                unlink($target);
            } elseif (is_dir($target)) {
                $this->removeDirectory($target);
            } else {
                unlink($target);
            }
        }

        return symlink($source, $target);
    }

    /**
     * Copy directory recursively
     *
     * @param string $source Source directory
     * @param string $target Target directory
     * @return bool True on success
     */
    private function copyDirectory(string $source, string $target): bool
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $iterator->getSubPathname();

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }

        return true;
    }

    /**
     * Clean published assets for a module
     *
     * @param string $moduleName Module name
     * @return bool True on success
     */
    public function clean(string $moduleName): bool
    {
        $modulePathSegment = str_replace('_', '/', $moduleName);
        $targetPath = $this->basePath . self::STATIC_DIR . '/' . $modulePathSegment;

        if (!file_exists($targetPath)) {
            return true;
        }

        if (is_link($targetPath)) {
            return unlink($targetPath);
        }

        return $this->removeDirectory($targetPath);
    }

    /**
     * Clean all published assets
     *
     * @return bool True on success
     */
    public function cleanAll(): bool
    {
        $staticPath = $this->basePath . self::STATIC_DIR;

        if (!is_dir($staticPath)) {
            return true;
        }

        return $this->removeDirectory($staticPath);
    }

    /**
     * Remove directory recursively
     *
     * @param string $dir Directory to remove
     * @return bool True on success
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir) && !is_link($dir)) {
            return false;
        }

        // If it's a symlink, just unlink it
        if (is_link($dir)) {
            return unlink($dir);
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Set symlink mode
     *
     * @param bool $useSymlinks Use symlinks
     * @return void
     */
    public function setUseSymlinks(bool $useSymlinks): void
    {
        $this->useSymlinks = $useSymlinks;
    }
}
