<?php
declare(strict_types=1);

namespace Infinri\Admin\Model\Menu;

use SimpleXMLElement;

/**
 * Menu Loader
 * Loads and merges menu.xml files from all modules
 */
class MenuLoader
{
    private const MENU_XML_PATH = 'etc/adminhtml/menu.xml';
    
    private array $menuItems = [];
    private bool $loaded = false;
    
    /**
     * Get all menu items
     */
    public function getMenuItems(): array
    {
        if (!$this->loaded) {
            $this->loadMenuXml();
            $this->buildMenuTree();
            $this->loaded = true;
        }
        
        return $this->menuItems;
    }
    
    /**
     * Load menu.xml files from all modules
     */
    private function loadMenuXml(): void
    {
        // MenuLoader is at: app/Infinri/Admin/Model/Menu/MenuLoader.php
        // We need to go up 3 levels to reach app/Infinri
        $appDir = dirname(__DIR__, 3);  // app/Infinri
        
        if (!is_dir($appDir)) {
            error_log("MenuLoader: appDir not found: " . $appDir);
            return;
        }
        
        error_log("MenuLoader: Scanning modules in: " . $appDir);
        $modules = scandir($appDir);
        $rawItems = [];
        
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            
            $menuFile = $appDir . '/' . $module . '/' . self::MENU_XML_PATH;
            
            if (!file_exists($menuFile)) {
                continue;
            }
            
            error_log("MenuLoader: Found menu file: " . $menuFile);
            $xml = simplexml_load_file($menuFile);
            if ($xml === false) {
                error_log("MenuLoader: Failed to parse XML: " . $menuFile);
                continue;
            }
            
            // Parse menu items from this module
            foreach ($xml->menu->add as $item) {
                $id = (string)$item['id'];
                $rawItems[$id] = [
                    'id' => $id,
                    'title' => (string)$item['title'],
                    'module' => (string)$item['module'],
                    'sortOrder' => (int)($item['sortOrder'] ?? 100),
                    'parent' => (string)($item['parent'] ?? ''),
                    'action' => (string)($item['action'] ?? ''),
                    'resource' => (string)($item['resource'] ?? ''),
                    'icon' => (string)($item['icon'] ?? ''),
                ];
            }
        }
        
        // Sort by sortOrder
        uasort($rawItems, function($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });
        
        error_log("MenuLoader: Total menu items loaded: " . count($rawItems));
        $this->menuItems = $rawItems;
    }
    
    /**
     * Build hierarchical menu tree
     */
    private function buildMenuTree(): void
    {
        $tree = [];
        $items = $this->menuItems;
        
        // First pass: collect top-level items
        foreach ($items as $id => $item) {
            if (empty($item['parent'])) {
                $tree[$id] = $item;
                $tree[$id]['children'] = [];
            }
        }
        
        // Second pass: attach children to parents
        foreach ($items as $id => $item) {
            if (!empty($item['parent']) && isset($tree[$item['parent']])) {
                $tree[$item['parent']]['children'][$id] = $item;
            }
        }
        
        $this->menuItems = $tree;
    }
}
