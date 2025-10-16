<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Asset;

use Infinri\Core\Api\AssetRepositoryInterface;

/**
 * Asset Repository
 * 
 * Manages CSS and JavaScript asset registration from modules
 * Assets are stored with their module reference (Module_Name::path/to/file.ext)
 */
class Repository implements AssetRepositoryInterface
{
    /**
     * Registered CSS assets
     *
     * @var array<string, array>
     */
    private array $css = [];

    /**
     * Registered JavaScript assets
     *
     * @var array<string, array>
     */
    private array $js = [];

    /**
     * Add CSS asset
     *
     * @param string $path Asset path (Module_Name::css/style.css)
     * @param array $attributes Additional attributes (media, rel, etc.)
     * @param int $priority Load order priority (lower = earlier)
     * @return void
     */
    public function addCss(string $path, array $attributes = [], int $priority = 0): void
    {
        $this->css[$path] = [
            'path' => $path,
            'attributes' => $attributes,
            'priority' => $priority,
        ];
    }

    /**
     * Add JavaScript asset
     *
     * @param string $path Asset path (Module_Name::js/app.js)
     * @param array $attributes Additional attributes (async, defer, etc.)
     * @param int $priority Load order priority (lower = earlier)
     * @return void
     */
    public function addJs(string $path, array $attributes = [], int $priority = 0): void
    {
        $this->js[$path] = [
            'path' => $path,
            'attributes' => $attributes,
            'priority' => $priority,
        ];
    }

    /**
     * Get all registered CSS assets
     * Returns assets sorted by priority (lower priority first)
     *
     * @return array Array of CSS assets sorted by priority
     */
    public function getAllCss(): array
    {
        return $this->sortAssetsByPriority($this->css);
    }

    /**
     * Get all registered JavaScript assets
     * Returns assets sorted by priority (lower priority first)
     *
     * @return array Array of JS assets sorted by priority
     */
    public function getAllJs(): array
    {
        return $this->sortAssetsByPriority($this->js);
    }

    /**
     * Remove CSS asset by path
     *
     * @param string $path Asset path
     * @return void
     */
    public function removeCss(string $path): void
    {
        unset($this->css[$path]);
    }

    /**
     * Remove JavaScript asset by path
     *
     * @param string $path Asset path
     * @return void
     */
    public function removeJs(string $path): void
    {
        unset($this->js[$path]);
    }

    /**
     * Clear all registered assets
     *
     * @return void
     */
    public function clear(): void
    {
        $this->css = [];
        $this->js = [];
    }

    /**
     * Sort assets by priority
     * Lower priority values come first
     *
     * @param array $assets Assets to sort
     * @return array Sorted assets
     */
    private function sortAssetsByPriority(array $assets): array
    {
        uasort($assets, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return $assets;
    }
}
