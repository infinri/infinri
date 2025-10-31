<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Cache\Pool;
use Infinri\Core\App\Request;
use SimpleXMLElement;

/**
 * Layout Loader
 * 
 * Loads layout XML files from modules based on handles (e.g., 'default', 'cms_index_index').
 * Supports caching for improved performance.
 */
class Loader
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    private ?string $currentArea = null;

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ?Pool $cachePool = null,
        private readonly ?Request $request = null
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
        // Check cache if available
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey($handle);
            $cached = $this->cachePool->get($cacheKey);
            
            if ($cached !== null && is_array($cached)) {
                // Reconstruct SimpleXMLElement objects from cached XML strings
                return $this->unserializeLayouts($cached);
            }
        }
        
        // Load from files
        $layouts = $this->loadFromFiles($handle);
        
        // Store in cache
        if ($this->isCacheEnabled() && !empty($layouts)) {
            $this->cachePool->set(
                $this->getCacheKey($handle),
                $this->serializeLayouts($layouts),
                self::CACHE_TTL
            );
        }
        
        return $layouts;
    }

    /**
     * Load layouts from files
     *
     * @param string $handle
     * @return array<string, SimpleXMLElement>
     */
    private function loadFromFiles(string $handle): array
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
     * Serialize layouts for caching
     *
     * @param array<string, SimpleXMLElement> $layouts
     * @return array<string, string>
     */
    private function serializeLayouts(array $layouts): array
    {
        $serialized = [];
        foreach ($layouts as $moduleName => $xml) {
            $serialized[$moduleName] = $xml->asXML();
        }
        return $serialized;
    }

    /**
     * Unserialize layouts from cache
     *
     * @param array<string, string> $serialized
     * @return array<string, SimpleXMLElement>
     */
    private function unserializeLayouts(array $serialized): array
    {
        $layouts = [];
        foreach ($serialized as $moduleName => $xmlString) {
            if ($xmlString !== false) {
                $xml = simplexml_load_string($xmlString);
                if ($xml !== false) {
                    $layouts[$moduleName] = $xml;
                }
            }
        }
        return $layouts;
    }

    /**
     * Check if caching is enabled
     *
     * @return bool
     */
    private function isCacheEnabled(): bool
    {
        return $this->cachePool !== null 
            && filter_var($_ENV['CACHE_LAYOUT_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get cache key for handle
     *
     * @param string $handle
     * @return string
     */
    private function getCacheKey(string $handle): string
    {
        $modules = $this->moduleManager->getModulesInOrder();
        return 'layout_' . $handle . '_' . md5(implode('|', $modules));
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
     * Set area explicitly (for testing)
     * @param string $area 'frontend' or 'adminhtml'
     * @return void
     */
    public function setArea(string $area): void
    {
        $this->currentArea = $area;
    }
    
    /**
     * Detect current area (admin or frontend)
     */
    private function detectArea(): string
    {
        if ($this->currentArea !== null) {
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
     * Get layout directory paths for a module
     *
     * @param string $modulePath Module base path
     * @return array<string> Layout directory paths
     */
    private function getLayoutDirectories(string $modulePath): array
    {
        $area = $this->detectArea();
        
        // If we can detect a specific area, prioritize it
        if ($this->request !== null) {
            // Request available - use detected area first
            return [
                $modulePath . '/view/' . $area . '/layout',  // Area-specific (detected)
                $modulePath . '/view/base/layout',           // Base layouts
                $modulePath . '/view/layout',                // Shared layouts
                $modulePath . '/etc/layout',                 // Config layouts
            ];
        }
        
        // No request - check both areas (for tests/CLI)
        return [
            $modulePath . '/view/adminhtml/layout',      // Admin area
            $modulePath . '/view/frontend/layout',       // Frontend area
            $modulePath . '/view/base/layout',           // Base layouts
            $modulePath . '/view/layout',                // Shared layouts
            $modulePath . '/etc/layout',                 // Config layouts
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
