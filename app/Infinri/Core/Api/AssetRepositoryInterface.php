<?php

declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Asset Repository Interface
 * 
 * Manages CSS and JavaScript asset registration from modules
 */
interface AssetRepositoryInterface
{
    /**
     * Add CSS asset
     *
     * @param string $path Asset path (Module_Name::css/style.css)
     * @param array $attributes Additional attributes (media, rel, etc.)
     * @param int $priority Load order priority (lower = earlier)
     * @return void
     */
    public function addCss(string $path, array $attributes = [], int $priority = 0): void;

    /**
     * Add JavaScript asset
     *
     * @param string $path Asset path (Module_Name::js/app.js)
     * @param array $attributes Additional attributes (async, defer, etc.)
     * @param int $priority Load order priority (lower = earlier)
     * @return void
     */
    public function addJs(string $path, array $attributes = [], int $priority = 0): void;

    /**
     * Get all registered CSS assets
     *
     * @return array Array of CSS assets sorted by priority
     */
    public function getAllCss(): array;

    /**
     * Get all registered JavaScript assets
     *
     * @return array Array of JS assets sorted by priority
     */
    public function getAllJs(): array;

    /**
     * Remove CSS asset by path
     *
     * @param string $path Asset path
     * @return void
     */
    public function removeCss(string $path): void;

    /**
     * Remove JavaScript asset by path
     *
     * @param string $path Asset path
     * @return void
     */
    public function removeJs(string $path): void;

    /**
     * Clear all registered assets
     *
     * @return void
     */
    public function clear(): void;
}
