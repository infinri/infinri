<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Core\Model\Message\MessageManager;

/**
 * Menu Save Controller
 * 
 * Saves menu data and processes CMS page selections
 */
class Save
{
    /**
     * Constructor
     *
     * @param MenuRepository $menuRepository
     * @param MenuItemRepository $menuItemRepository
     * @param MessageManager $messageManager
     */
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly MenuItemRepository $menuItemRepository,
        private readonly MessageManager $messageManager
    ) {}

    /**
     * Execute action
     *
     * @return Response
     */
    public function execute(Request $request): Response
    {
        try {
            // Get menu ID if editing
            $menuId = $request->getParam('menu_id') ? (int)$request->getParam('menu_id') : null;
            
            // Create or update menu
            if ($menuId) {
                $menu = $this->menuRepository->getById($menuId);
                if (!$menu) {
                    throw new \RuntimeException('Menu not found');
                }
            } else {
                $menu = $this->menuRepository->create();
            }
            
            $menu->setTitle($request->getParam('title', ''));
            $menu->setIdentifier($request->getParam('identifier', ''));
            $menu->setIsActive(!empty($request->getParam('is_active')));
            
            $this->menuRepository->save($menu);
            
            // Process CMS page selections
            $cmsPages = $request->getParam('cms_pages', []);
            $this->processCmsPages($menu->getMenuId(), $cmsPages);
            
            $this->messageManager->addSuccess('Menu saved successfully');
            
            // Check if "Save & Continue"
            if ($request->getParam('save_and_continue')) {
                return (new Response())->setRedirect('/admin/menu/menu/edit?id=' . $menu->getMenuId());
            }
            
            return (new Response())->setRedirect('/admin/menu/menu/index');
            
        } catch (\Exception $e) {
            $this->messageManager->addError('Error saving menu: ' . $e->getMessage());
            
            // Redirect back to form
            $backMenuId = $request->getParam('menu_id');
            $url = $backMenuId ? '/admin/menu/menu/edit?id=' . $backMenuId : '/admin/menu/menu/edit';
            
            return (new Response())->setRedirect($url);
        }
    }
    
    /**
     * Process CMS page selections and create/update/delete menu items
     *
     * @param int $menuId
     * @param array $cmsPages
     * @return void
     */
    private function processCmsPages(int $menuId, array $cmsPages): void
    {
        // Get existing menu items for this menu
        $existingItems = $this->menuItemRepository->getByMenuId($menuId);
        $existingItemsByPage = [];
        
        foreach ($existingItems as $item) {
            if ($item->getLinkType() === 'cms_page' && $item->getResourceId()) {
                $existingItemsByPage[$item->getResourceId()] = $item;
            }
        }
        
        // Process each page selection
        foreach ($cmsPages as $pageId => $pageData) {
            $isSelected = !empty($pageData['selected']);
            $sortOrder = (int)($pageData['sort_order'] ?? 10);
            
            if ($isSelected) {
                // Create or update menu item
                if (isset($existingItemsByPage[$pageId])) {
                    // Update existing item
                    $item = $existingItemsByPage[$pageId];
                    $item->setSortOrder($sortOrder);
                    $this->menuItemRepository->save($item);
                    
                    // Remove from tracking array
                    unset($existingItemsByPage[$pageId]);
                } else {
                    // Create new item
                    $item = $this->menuItemRepository->create();
                    $item->setMenuId($menuId);
                    $item->setLinkType('cms_page');
                    $item->setResourceId($pageId);
                    $item->setTitle($pageData['title'] ?? 'Page ' . $pageId); // Will be updated from page
                    $item->setSortOrder($sortOrder);
                    $item->setIsActive(true);
                    $item->setParentItemId(null); // Root level for now
                    
                    $this->menuItemRepository->save($item);
                }
            } elseif (isset($existingItemsByPage[$pageId])) {
                // Page was unchecked, delete the menu item
                $this->menuItemRepository->delete($existingItemsByPage[$pageId]->getItemId());
                unset($existingItemsByPage[$pageId]);
            }
        }
        
        // Delete any remaining items that are no longer selected
        foreach ($existingItemsByPage as $item) {
            $this->menuItemRepository->delete($item->getItemId());
        }
    }
}
