<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use Infinri\Core\Model\Module\ModuleManager;

/**
 * Config Loader
 * 
 * Loads and merges configuration from all enabled modules.
 */
class Loader
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly Reader $configReader
    ) {
    }

    /**
     * Load configuration from all enabled modules
     *
     * @return array<string, mixed> Merged configuration array
     */
    public function load(): array
    {
        $config = [];
        
        // Get modules in dependency order
        $modules = $this->moduleManager->getModulesInOrder();
        
        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            
            if (!$moduleData || !isset($moduleData['path'])) {
                continue;
            }
            
            $moduleConfig = $this->configReader->read($moduleData['path']);
            
            if ($moduleConfig !== null) {
                $config = $this->mergeConfig($config, $moduleConfig);
            }
        }
        
        return $config;
    }

    /**
     * Merge two configuration arrays recursively
     *
     * @param array<string, mixed> $base Base configuration
     * @param array<string, mixed> $new New configuration to merge
     * @return array<string, mixed>
     */
    private function mergeConfig(array $base, array $new): array
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // Both are arrays - merge recursively
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                // Overwrite with new value
                $base[$key] = $value;
            }
        }
        
        return $base;
    }

    /**
     * Load configuration for a specific scope
     *
     * @param string $scope Scope type (default, website, store)
     * @return array<string, mixed>
     */
    public function loadByScope(string $scope): array
    {
        $allConfig = $this->load();
        
        // Return configuration for the specific scope if it exists
        return $allConfig[$scope] ?? [];
    }
}
