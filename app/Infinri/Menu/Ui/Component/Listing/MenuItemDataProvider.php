<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Listing;

use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Core\App\Request;

/**
 * Menu Item Listing Data Provider
 * 
 * Provides data for the menu item grid
 */
class MenuItemDataProvider
{
    /**
     * Constructor
     *
     * @param MenuItemRepository $menuItemRepository
     * @param Request $request
     */
    public function __construct(
        private readonly MenuItemRepository $menuItemRepository,
        private readonly Request $request
    ) {}

    /**
     * Get data for grid
     *
     * @return array
     */
    public function getData(): array
    {
        $menuId = (int)$this->request->getParam('menu_id');
        
        if (!$menuId) {
            return [];
        }
        
        $items = $this->menuItemRepository->getByMenuId($menuId);
        
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'item_id' => $item->getItemId(),
                'menu_id' => $item->getMenuId(),
                'parent_item_id' => $item->getParentItemId(),
                'title' => $item->getTitle(),
                'link_type' => $this->formatLinkType($item->getLinkType()),
                'resource_id' => $item->getResourceId(),
                'sort_order' => $item->getSortOrder(),
                'is_active' => $item->isActive() ? 'Yes' : 'No',
            ];
        }
        
        return $data;
    }
    
    /**
     * Format link type for display
     *
     * @param string $linkType
     * @return string
     */
    private function formatLinkType(string $linkType): string
    {
        return match($linkType) {
            'cms_page' => 'CMS Page',
            'category' => 'Category',
            'custom_url' => 'Custom URL',
            'external' => 'External Link',
            default => $linkType
        };
    }
}
