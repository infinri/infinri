<?php
declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Manages registration and retrieval of components (modules, themes, libraries, languages).
 */
interface ComponentRegistrarInterface
{
    /**
     * Component types
     */
    public const MODULE = 'module';
    public const THEME = 'theme';
    public const LIBRARY = 'library';
    public const LANGUAGE = 'language';

    /**
     * Register a component
     *
     * @param string $type Component type (module, theme, library, language)
     * @param string $name Component name (e.g., 'Infinri_Core')
     * @param string $path Absolute path to component directory
     * @return void
     */
    public function registerComponent(string $type, string $name, string $path): void;

    /**
     * Get all registered components of a specific type
     *
     * @param string $type Component type
     * @return array<string, string> Array of component_name => path
     */
    public function getPaths(string $type): array;

    /**
     * Get path for a specific component
     *
     * @param string $type Component type
     * @param string $name Component name
     * @return string|null Path to component or null if not found
     */
    public function getPath(string $type, string $name): ?string;

    /**
     * Check if a component is registered
     *
     * @param string $type Component type
     * @param string $name Component name
     * @return bool
     */
    public function isRegistered(string $type, string $name): bool;
}
