<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Model\Module\ModuleManager;
use SimpleXMLElement;

/**
 * Layout Loader
 * 
 * Loads layout XML files from modules based on handles (e.g., 'default', 'cms_index_index').
 */
class Loader
{
    public function __construct(
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * Load layout files for a specific handle
     *
     * @param string $handle Layout handle (e.g., 'default', 'cms_index_index')
     * @return array<string, SimpleXMLElement> Array of module name => XML content
     */
    public function load(string $handle): array
    {
        $layouts = [];
        
        // Get modules in dependency order
        $modules = $this->moduleManager->getModulesInOrder();
        
        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            
            if (!$moduleData || !isset($moduleData['path'])) {
                continue;
            }
            
            // Try to load layout file for this handle
            $xml = $this->loadLayoutFile($moduleData['path'], $handle);
            
            if ($xml !== null) {
                $layouts[$moduleName] = $xml;
            }
        }
        
        return $layouts;
    }

    /**
     * Load layout file from module
     *
     * @param string $modulePath Module base path
     * @param string $handle Layout handle
     * @return SimpleXMLElement|null
     */
    private function loadLayoutFile(string $modulePath, string $handle): ?SimpleXMLElement
    {
        $layoutDirs = $this->getLayoutDirectories($modulePath);
        
        foreach ($layoutDirs as $dir) {
            $filePath = $dir . '/' . $handle . '.xml';
            if (file_exists($filePath)) {
                return $this->loadXml($filePath);
            }
        }
        
        return null;
    }

    /**
     * Get layout directory paths for a module
     *
     * @param string $modulePath Module base path
     * @return array<string> Layout directory paths
     */
    private function getLayoutDirectories(string $modulePath): array
    {
        return [
            $modulePath . '/view/frontend/layout',
            $modulePath . '/view/layout',
            $modulePath . '/etc/layout',
        ];
    }

    /**
     * Load and validate XML file
     *
     * @param string $filePath
     * @return SimpleXMLElement|null
     */
    private function loadXml(string $filePath): ?SimpleXMLElement
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        
        try {
            $xml = simplexml_load_file($filePath);
            
            if ($xml === false) {
                libxml_clear_errors();
                return null;
            }

            return $xml;
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }
    }

    /**
     * Get all available handles from all modules
     *
     * @return array<string> List of unique handles
     */
    public function getAvailableHandles(): array
    {
        $handles = [];
        
        $modules = $this->moduleManager->getModulesInOrder();
        
        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            
            if (!$moduleData || !isset($moduleData['path'])) {
                continue;
            }
            
            $layoutDirs = $this->getLayoutDirectories($moduleData['path']);
            
            foreach ($layoutDirs as $dir) {
                // Skip if directory doesn't exist (faster than is_dir check in loop)
                if (!is_dir($dir)) {
                    continue;
                }
                
                // Use scandir instead of glob for better performance
                $files = @scandir($dir);
                if ($files === false) {
                    continue;
                }
                
                foreach ($files as $file) {
                    // Only process .xml files
                    if (substr($file, -4) === '.xml') {
                        $handle = substr($file, 0, -4);
                        $handles[$handle] = true;
                    }
                }
            }
        }
        
        return array_keys($handles);
    }
}
