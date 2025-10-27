<?php
declare(strict_types=1);

namespace Infinri\Admin\Block;

use Infinri\Core\Block\Template;
use Infinri\Admin\Model\Menu\MenuLoader;

/**
 * Admin Menu Block
 * Renders the admin navigation sidebar
 */
class Menu extends Template
{
    public function __construct(
        private readonly MenuLoader $menuLoader
    ) {
    }

    /**
     * Get menu items loaded from menu.xml files
     */
    public function getMenuItems(): array
    {
        error_log("Menu::getMenuItems() called");
        $items = $this->menuLoader->getMenuItems();
        error_log("Menu items loaded: " . count($items));
        
        // Add URL and active state to each item
        foreach ($items as &$item) {
            if (!empty($item['action'])) {
                $item['url'] = '/' . ltrim($item['action'], '/');
            }
            $item['active'] = $this->isActive($item);
            
            // Process child items
            if (!empty($item['children'])) {
                foreach ($item['children'] as &$child) {
                    if (!empty($child['action'])) {
                        $child['url'] = '/' . ltrim($child['action'], '/');
                    }
                    $child['active'] = $this->isActive($child);
                }
            }
        }
        
        return $items;
    }

    /**
     * Check if menu item is active
     */
    public function isActive(array $item): bool
    {
        return $item['active'] ?? false;
    }

    /**
     * Check if menu item has children
     */
    public function hasChildren(array $item): bool
    {
        return !empty($item['children']);
    }

    /**
     * Get default template
     */
    public function getTemplate(): string
    {
        return $this->template ?: 'Infinri_Theme::html/menu.phtml';
    }
}
