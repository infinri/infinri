<?php

declare(strict_types=1);

namespace Infinri\Admin\Model\Menu;

use SimpleXMLElement;

/**
 * Menu Builder
 * 
 * Builds admin navigation menu from XML configuration files
 * Discovers menu.xml from all enabled modules
 */
class Builder
{
    private string $appDir;
    
    /** @var Item[] */
    private array $items = [];
    
    public function __construct()
    {
        $this->appDir = dirname(__DIR__, 4);
    }
    
    /**
     * Build complete menu tree from all modules
     *
     * @return Item[]
     */
    public function build(): array
    {
        $this->items = [];
        
        // Discover and load menu XML files from all modules
        $menuFiles = $this->discoverMenuFiles();
        
        foreach ($menuFiles as $file) {
            $this->loadMenuFile($file);
        }
        
        // Build tree structure (root items only, children attached)
        return $this->buildTree();
    }
    
    /**
     * Discover menu.xml files from all enabled modules
     *
     * @return string[]
     */
    private function discoverMenuFiles(): array
    {
        $files = [];
        $modulesDir = $this->appDir . '/app/Infinri';
        
        if (!is_dir($modulesDir)) {
            return $files;
        }
        
        $modules = scandir($modulesDir);
        
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }
            
            $menuFile = $modulesDir . '/' . $module . '/etc/adminhtml/menu.xml';
            
            if (file_exists($menuFile)) {
                $files[] = $menuFile;
            }
        }
        
        return $files;
    }
    
    /**
     * Load menu items from XML file
     */
    private function loadMenuFile(string $file): void
    {
        $xml = simplexml_load_file($file);
        
        if (!$xml || !isset($xml->menu)) {
            return;
        }
        
        foreach ($xml->menu->add as $node) {
            $this->addItemFromXml($node);
        }
    }
    
    /**
     * Create menu item from XML node
     */
    private function addItemFromXml(SimpleXMLElement $node): void
    {
        $id = (string) $node['id'];
        $title = (string) $node['title'];
        $action = isset($node['action']) ? (string) $node['action'] : null;
        $module = isset($node['module']) ? (string) $node['module'] : null;
        $resource = isset($node['resource']) ? (string) $node['resource'] : null;
        $sortOrder = isset($node['sortOrder']) ? (int) $node['sortOrder'] : 0;
        $parent = isset($node['parent']) ? (string) $node['parent'] : null;
        
        $item = new Item(
            $id,
            $title,
            $action,
            $module,
            $resource,
            $sortOrder,
            $parent
        );
        
        $this->items[$id] = $item;
    }
    
    /**
     * Build hierarchical tree from flat items list
     *
     * @return Item[]
     */
    private function buildTree(): array
    {
        $tree = [];
        
        // First, separate root items from children
        foreach ($this->items as $item) {
            if (!$item->getParent()) {
                $tree[$item->getId()] = $item;
            }
        }
        
        // Then attach children to their parents
        foreach ($this->items as $item) {
            $parentId = $item->getParent();
            
            if ($parentId && isset($this->items[$parentId])) {
                $this->items[$parentId]->addChild($item);
            }
        }
        
        // Sort root items by sort order
        usort($tree, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());
        
        return $tree;
    }
    
    /**
     * Get menu item by ID
     */
    public function getItem(string $id): ?Item
    {
        return $this->items[$id] ?? null;
    }
    
    /**
     * Get all menu items (flat)
     *
     * @return Item[]
     */
    public function getAllItems(): array
    {
        return $this->items;
    }
}
