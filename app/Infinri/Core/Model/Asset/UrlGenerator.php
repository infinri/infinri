<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Asset;

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Api\ComponentRegistrarInterface;

/**
 * Asset URL Generator
 * 
 * Resolves asset paths (Module_Name::path/to/file.ext) to public URLs
 * Example: Infinri_Core::css/style.css -> /static/Infinri/Core/css/style.css
 */
class UrlGenerator
{
    /**
     * Base URL for static assets
     */
    private const STATIC_BASE_URL = '/static';

    /**
     * Component Registrar
     *
     * @var ComponentRegistrarInterface
     */
    private ComponentRegistrarInterface $componentRegistrar;

    /**
     * Cache versioning enabled
     *
     * @var bool
     */
    private bool $versioningEnabled;

    /**
     * Asset version (for cache busting)
     *
     * @var string
     */
    private string $version;

    /**
     * Constructor
     *
     * @param ComponentRegistrarInterface|null $componentRegistrar
     * @param bool $versioningEnabled Enable cache busting
     * @param string $version Asset version for cache busting
     */
    public function __construct(
        ?ComponentRegistrarInterface $componentRegistrar = null,
        bool $versioningEnabled = false,
        string $version = '1.0.0'
    ) {
        $this->componentRegistrar = $componentRegistrar ?? ComponentRegistrar::getInstance();
        $this->versioningEnabled = $versioningEnabled;
        $this->version = $version;
    }

    /**
     * Generate URL for asset
     *
     * @param string $assetPath Asset path (Module_Name::path/to/file.ext)
     * @return string Public URL to asset
     * @throws \InvalidArgumentException If asset path is invalid
     */
    public function getUrl(string $assetPath): string
    {
        if (!$this->isValidAssetPath($assetPath)) {
            throw new \InvalidArgumentException("Invalid asset path format: {$assetPath}");
        }

        [$moduleName, $filePath] = explode('::', $assetPath, 2);

        // Convert module name to path format (Infinri_Core -> Infinri/Core)
        $modulePathSegment = str_replace('_', '/', $moduleName);

        // Build URL: /static/Infinri/Core/css/style.css
        $url = self::STATIC_BASE_URL . '/' . $modulePathSegment . '/' . $filePath;

        // Add version parameter for cache busting
        if ($this->versioningEnabled) {
            $url .= '?v=' . $this->version;
        }

        return $url;
    }

    /**
     * Get URL for multiple assets
     *
     * @param array $assets Array of asset data (from Repository)
     * @return array Array of URLs indexed by asset path
     */
    public function getUrls(array $assets): array
    {
        $urls = [];

        foreach ($assets as $asset) {
            $path = $asset['path'];
            $urls[$path] = $this->getUrl($path);
        }

        return $urls;
    }

    /**
     * Validate asset path format
     *
     * @param string $assetPath Asset path to validate
     * @return bool True if valid
     */
    private function isValidAssetPath(string $assetPath): bool
    {
        // Must contain "::" separator
        if (strpos($assetPath, '::') === false) {
            return false;
        }

        // Must have both module name and file path
        $parts = explode('::', $assetPath, 2);
        if (count($parts) !== 2) {
            return false;
        }

        // Both parts must be non-empty
        if (empty($parts[0]) || empty($parts[1])) {
            return false;
        }

        return true;
    }

    /**
     * Set asset version for cache busting
     *
     * @param string $version Version string
     * @return void
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * Enable or disable versioning
     *
     * @param bool $enabled Enable versioning
     * @return void
     */
    public function setVersioningEnabled(bool $enabled): void
    {
        $this->versioningEnabled = $enabled;
    }
}
