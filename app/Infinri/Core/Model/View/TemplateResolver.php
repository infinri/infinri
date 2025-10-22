<?php
declare(strict_types=1);

namespace Infinri\Core\Model\View;

use Infinri\Core\Model\Module\ModuleManager;

/**
 * Template Resolver
 * 
 * Resolves template file paths from module directories with fallback support.
 */
class TemplateResolver
{
    /**
     * @var array<string, string> Template file cache
     */
    private array $templateCache = [];

    public function __construct(
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * Resolve template file path
     *
     * @param string $templatePath Template path (e.g., 'Infinri_Core::template/file.phtml')
     * @return string|null Absolute file path or null if not found
     */
    public function resolve(string $templatePath): ?string
    {
        // Check cache
        if (isset($this->templateCache[$templatePath])) {
            return $this->templateCache[$templatePath];
        }

        // Parse template path (Module_Name::path/to/template.phtml)
        if (!str_contains($templatePath, '::')) {
            return null;
        }

        [$moduleName, $filePath] = explode('::', $templatePath, 2);

        // Get module path
        $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
        
        if (!$moduleData || !isset($moduleData['path'])) {
            return null;
        }

        // Try different template locations
        $possiblePaths = [
            $moduleData['path'] . '/view/adminhtml/templates/' . $filePath,  // Admin area
            $moduleData['path'] . '/view/frontend/templates/' . $filePath,   // Frontend area
            $moduleData['path'] . '/view/base/templates/' . $filePath,       // Base/shared
            $moduleData['path'] . '/view/templates/' . $filePath,            // Legacy
            $moduleData['path'] . '/templates/' . $filePath,                 // Legacy
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->templateCache[$templatePath] = $path;
                return $path;
            }
        }

        return null;
    }

    /**
     * Clear template cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->templateCache = [];
    }
}
