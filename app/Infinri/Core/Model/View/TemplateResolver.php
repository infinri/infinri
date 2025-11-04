<?php

declare(strict_types=1);

namespace Infinri\Core\Model\View;

use Infinri\Core\App\Request;
use Infinri\Core\Model\Module\ModuleManager;

/**
 * Resolves template file paths from module directories with fallback support.
 */
class TemplateResolver
{
    /**
     * @var array<string, string> Template file cache
     *                            DISABLED: Causing issues with template updates during development
     */
    private array $templateCache = [];

    private ?string $currentArea = null;

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ?Request $request = null
    ) {
    }

    /**
     * Resolve template file path.
     *
     * @param string $templatePath Template path (e.g., 'Infinri_Core::template/file.phtml')
     *
     * @return string|null Absolute file path or null if not found
     */
    public function resolve(string $templatePath): ?string
    {
        // Check cache
        if (isset($this->templateCache[$templatePath])) {
            return $this->templateCache[$templatePath];
        }

        // Parse template path (Module_Name::path/to/template.phtml)
        if (! str_contains($templatePath, '::')) {
            return null;
        }

        [$moduleName, $filePath] = explode('::', $templatePath, 2);

        if (! $this->isValidTemplatePath($filePath)) {
            throw new \InvalidArgumentException("Invalid template path: Directory traversal attempt detected in '$filePath'");
        }

        // Get module path
        $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);

        if (! $moduleData || ! isset($moduleData['path'])) {
            return null;
        }

        // Detect current area
        $area = $this->detectArea();

        // Try different template locations
        if (null !== $this->request) {
            // Request available - prioritize detected area
            $possiblePaths = [
                $moduleData['path'] . '/view/' . $area . '/templates/' . $filePath,  // Area-specific (detected)
                $moduleData['path'] . '/view/base/templates/' . $filePath,           // Base/shared
                $moduleData['path'] . '/view/templates/' . $filePath,                // Legacy
                $moduleData['path'] . '/templates/' . $filePath,                     // Legacy
            ];
        } else {
            // No request - check both areas (for tests/CLI)
            $possiblePaths = [
                $moduleData['path'] . '/view/adminhtml/templates/' . $filePath,      // Admin area
                $moduleData['path'] . '/view/frontend/templates/' . $filePath,       // Frontend area
                $moduleData['path'] . '/view/base/templates/' . $filePath,           // Base/shared
                $moduleData['path'] . '/view/templates/' . $filePath,                // Legacy
                $moduleData['path'] . '/templates/' . $filePath,                     // Legacy
            ];
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->templateCache[$templatePath] = $path;

                return $path;
            }
        }

        return null;
    }

    /**
     * Set area explicitly (for testing).
     *
     * @param string $area 'frontend' or 'adminhtml'
     */
    public function setArea(string $area): void
    {
        $this->currentArea = $area;
    }

    /**
     * Detect current area (admin or frontend).
     */
    private function detectArea(): string
    {
        if (null !== $this->currentArea) {
            return $this->currentArea;
        }

        // Check request path
        if ($this->request) {
            $path = $this->request->getPath();
            $this->currentArea = (str_starts_with($path, '/admin')) ? 'adminhtml' : 'frontend';
        } else {
            // Fallback to adminhtml if no request (for backward compatibility with tests)
            $this->currentArea = 'adminhtml';
        }

        return $this->currentArea;
    }

    /**
     * Clear template cache.
     */
    public function clearCache(): void
    {
        $this->templateCache = [];
    }

    /**
     * Validate template path to prevent directory traversal attacks.
     *
     * Blocks:
     * - Path traversal sequences (../, ..\)
     * - Absolute paths (/ at start)
     * - Null bytes (\0)
     * - Non-.phtml files
     *
     * @param string $filePath Template file path
     *
     * @return bool True if path is valid and safe
     */
    private function isValidTemplatePath(string $filePath): bool
    {
        // 1. Block path traversal attempts
        if (str_contains($filePath, '..')) {
            return false;
        }

        // 2. Block absolute paths
        if (str_starts_with($filePath, '/') || str_starts_with($filePath, '\\')) {
            return false;
        }

        // 3. Block null bytes
        if (str_contains($filePath, "\0")) {
            return false;
        }

        // 4. Block backslashes (Windows path separators shouldn't be used)
        if (str_contains($filePath, '\\')) {
            return false;
        }

        // 5. Must end with .phtml extension
        if (! str_ends_with($filePath, '.phtml')) {
            return false;
        }

        // 6. Whitelist allowed characters: alphanumeric, /, -, _, .
        if (! preg_match('/^[a-zA-Z0-9\/_.-]+$/', $filePath)) {
            return false;
        }

        if ('' === trim($filePath)) {
            return false;
        }

        return true;
    }
}
