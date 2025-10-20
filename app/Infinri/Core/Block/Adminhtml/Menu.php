<?php
declare(strict_types=1);

namespace Infinri\Core\Block\Adminhtml;

use Infinri\Core\Block\Template;

/**
 * Admin Menu Block
 * Renders the admin navigation sidebar
 */
class Menu extends Template
{
    /**
     * @var array Menu structure loaded from menu.xml
     */
    private array $menuItems = [];

    /**
     * Get menu items
     */
    public function getMenuItems(): array
    {
        if (empty($this->menuItems)) {
            $this->menuItems = $this->loadMenuFromXml();
        }
        
        return $this->menuItems;
    }

    /**
     * Load menu structure from XML
     * TODO: Implement proper XML loading with MenuLoader
     */
    private function loadMenuFromXml(): array
    {
        // For now, return hardcoded structure matching menu.xml
        // In production, this would load from XML files
        return [
            [
                'id' => 'dashboard',
                'title' => 'Dashboard',
                'url' => '/admin/dashboard/index',
                'icon' => 'icon-dashboard',
                'active' => false,
            ],
            [
                'id' => 'content',
                'title' => 'Content',
                'icon' => 'icon-content',
                'active' => true,
                'children' => [
                    [
                        'id' => 'pages',
                        'title' => 'Pages',
                        'url' => '/admin/cms/page/index',
                        'active' => true,
                    ],
                    [
                        'id' => 'blocks',
                        'title' => 'Blocks',
                        'url' => '/admin/cms/block/index',
                        'active' => false,
                    ],
                ],
            ],
            [
                'id' => 'system',
                'title' => 'System',
                'icon' => 'icon-system',
                'active' => false,
                'children' => [
                    [
                        'id' => 'config',
                        'title' => 'Configuration',
                        'url' => '/admin/system/config/index',
                        'active' => false,
                    ],
                ],
            ],
        ];
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
        return $this->template ?: 'Infinri_Core::adminhtml/menu.phtml';
    }
}
