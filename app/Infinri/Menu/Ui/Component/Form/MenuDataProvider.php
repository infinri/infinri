<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Form;

use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Cms\Model\Repository\PageRepository;

/**
 * Menu Form Data Provider
 * 
 * Provides data for menu edit/create form including available CMS pages
 */
class MenuDataProvider
{
    /**
     * Constructor
     *
     * @param MenuRepository $menuRepository
     * @param MenuItemRepository $menuItemRepository
     * @param PageRepository $pageRepository
     */
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly MenuItemRepository $menuItemRepository,
        private readonly PageRepository $pageRepository
    ) {}

    /**
     * Get data for form
     *
     * @param int|null $menuId
     * @return array
     */
    public function getData(?int $menuId = null): array
    {
        $data = [
            'menu_id' => $menuId,
            'identifier' => '',
            'title' => '',
            'is_active' => true,
            'cms_pages' => $this->getAvailableCmsPages($menuId)
        ];
        
        if ($menuId !== null) {
            $menu = $this->menuRepository->getById($menuId);
            
            if ($menu) {
                $data['identifier'] = $menu->getIdentifier();
                $data['title'] = $menu->getTitle();
                $data['is_active'] = $menu->isActive();
            }
        }
        
        return $data;
    }
    
    /**
     * Get available CMS pages with selection status
     *
     * @param int|null $menuId
     * @return array
     */
    private function getAvailableCmsPages(?int $menuId): array
    {
        // Get all active CMS pages
        $pages = $this->pageRepository->getAll();
        $activePagesonly = array_filter($pages, fn($page) => $page->isActive());
        
        // Get currently selected pages for this menu (if editing)
        $selectedPages = [];
        if ($menuId) {
            $menuItems = $this->menuItemRepository->getByMenuId($menuId);
            foreach ($menuItems as $item) {
                if ($item->getLinkType() === 'cms_page' && $item->getResourceId()) {
                    $selectedPages[$item->getResourceId()] = [
                        'selected' => true,
                        'sort_order' => $item->getSortOrder(),
                        'item_id' => $item->getItemId()
                    ];
                }
            }
        }
        
        // Build available pages array
        $cmsPages = [];
        foreach ($activePagesonly as $page) {
            $pageId = $page->getId();
            $cmsPages[] = [
                'page_id' => $pageId,
                'title' => $page->getTitle(),
                'url_key' => $page->getUrlKey(),
                'selected' => isset($selectedPages[$pageId]),
                'sort_order' => $selectedPages[$pageId]['sort_order'] ?? 10,
                'item_id' => $selectedPages[$pageId]['item_id'] ?? null
            ];
        }
        
        return $cmsPages;
    }
}
