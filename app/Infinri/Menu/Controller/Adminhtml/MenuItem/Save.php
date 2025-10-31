<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\MenuItem;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Core\Model\Message\Manager as MessageManager;

/**
 * Menu Item Save Controller
 * 
 * Saves menu item data
 */
class Save
{
    /**
     * Constructor
     *
     * @param Request $request
     * @param Response $response
     * @param MenuItemRepository $menuItemRepository
     * @param MessageManager $messageManager
     */
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly MenuItemRepository $menuItemRepository,
        private readonly MessageManager $messageManager
    ) {}

    /**
     * Execute action
     *
     * @return Response
     */
    public function execute(): Response
    {
        try {
            $data = $this->request->getPostParams();
            
            if (empty($data)) {
                throw new \RuntimeException('No data provided');
            }
            
            $menuId = (int)($data['menu_id'] ?? 0);
            
            if (!$menuId) {
                throw new \RuntimeException('Menu ID is required');
            }
            
            // Create or update menu item
            if (!empty($data['item_id'])) {
                $item = $this->menuItemRepository->getById((int)$data['item_id']);
                if (!$item) {
                    throw new \RuntimeException('Menu item not found');
                }
            } else {
                $item = $this->menuItemRepository->create();
            }
            
            $item->setMenuId($menuId);
            $item->setTitle($data['title'] ?? '');
            $item->setLinkType($data['link_type'] ?? 'cms_page');
            $item->setResourceId(!empty($data['resource_id']) ? (int)$data['resource_id'] : null);
            $item->setCustomUrl($data['custom_url'] ?? null);
            $item->setParentItemId(!empty($data['parent_item_id']) ? (int)$data['parent_item_id'] : null);
            $item->setCssClass($data['css_class'] ?? null);
            $item->setIconClass($data['icon_class'] ?? null);
            $item->setOpenInNewTab(!empty($data['open_in_new_tab']));
            $item->setSortOrder((int)($data['sort_order'] ?? 0));
            $item->setIsActive(!empty($data['is_active']));
            
            $this->menuItemRepository->save($item);
            
            $this->messageManager->addSuccessMessage('Menu item saved successfully');
            
            // Check if "Save & Continue"
            if ($this->request->getParam('back') === 'continue') {
                return $this->response->setRedirect('/admin/menu/menuitem/edit?id=' . $item->getItemId() . '&menu_id=' . $menuId);
            }
            
            return $this->response->setRedirect('/admin/menu/menuitem/index?menu_id=' . $menuId);
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Error saving menu item: ' . $e->getMessage());
            
            // Redirect back to form
            $itemId = $data['item_id'] ?? null;
            $menuId = $data['menu_id'] ?? 0;
            $url = $itemId 
                ? '/admin/menu/menuitem/edit?id=' . $itemId . '&menu_id=' . $menuId
                : '/admin/menu/menuitem/edit?menu_id=' . $menuId;
            
            return $this->response->setRedirect($url);
        }
    }
}
