<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Form;

use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Cms\Model\Repository\PageRepository;

/**
 * Menu Item Form Data Provider
 * 
 * Provides data for menu item edit/create form
 */
class MenuItemDataProvider
{
    /**
     * Constructor
     *
     * @param MenuItemRepository $menuItemRepository
     * @param MenuRepository $menuRepository
     * @param PageRepository $pageRepository
     */
    public function __construct(
        private readonly MenuItemRepository $menuItemRepository,
        private readonly MenuRepository $menuRepository,
        private readonly PageRepository $pageRepository
    ) {}

    /**
     * Get data for form
     *
     * @param int|null $itemId
     * @param int|null $menuId
     * @return array
     */
    public function getData(?int $itemId = null, ?int $menuId = null): array
    {
        if ($itemId === null) {
            return [
                'item_id' => null,
                'menu_id' => $menuId,
                'parent_item_id' => null,
                'title' => '',
                'link_type' => 'cms_page',
                'resource_id' => null,
                'custom_url' => '',
                'css_class' => '',
                'icon_class' => '',
                'open_in_new_tab' => false,
                'sort_order' => 0,
                'is_active' => true
            ];
        }
        
        $item = $this->menuItemRepository->getById($itemId);
        
        if (!$item) {
            return [];
        }
        
        return [
            'item_id' => $item->getItemId(),
            'menu_id' => $item->getMenuId(),
            'parent_item_id' => $item->getParentItemId(),
            'title' => $item->getTitle(),
            'link_type' => $item->getLinkType(),
            'resource_id' => $item->getResourceId(),
            'custom_url' => $item->getCustomUrl() ?? '',
            'css_class' => $item->getCssClass() ?? '',
            'icon_class' => $item->getIconClass() ?? '',
            'open_in_new_tab' => $item->getOpenInNewTab(),
            'sort_order' => $item->getSortOrder(),
            'is_active' => $item->isActive()
        ];
    }
    
    /**
     * Get available CMS pages for dropdown
     *
     * @return array
     */
    public function getCmsPageOptions(): array
    {
        $pages = $this->pageRepository->getAll();
        
        $options = [['value' => '', 'label' => '-- Select Page --']];
        
        foreach ($pages as $page) {
            $options[] = [
                'value' => $page->getId(),
                'label' => $page->getTitle() . ' (' . $page->getUrlKey() . ')'
            ];
        }
        
        return $options;
    }
    
    /**
     * Get available parent items for dropdown
     *
     * @param int $menuId
     * @param int|null $excludeId
     * @return array
     */
    public function getParentItemOptions(int $menuId, ?int $excludeId = null): array
    {
        $items = $this->menuItemRepository->getByMenuId($menuId);
        
        $options = [['value' => '', 'label' => '-- No Parent (Root Level) --']];
        
        foreach ($items as $item) {
            // Don't allow item to be its own parent
            if ($excludeId && $item->getItemId() === $excludeId) {
                continue;
            }
            
            $options[] = [
                'value' => $item->getItemId(),
                'label' => $item->getTitle()
            ];
        }
        
        return $options;
    }
}
