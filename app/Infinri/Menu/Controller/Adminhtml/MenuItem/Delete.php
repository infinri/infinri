<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\MenuItem;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuItemRepository;
use Infinri\Core\Model\Message\Manager as MessageManager;

/**
 * Menu Item Delete Controller
 * 
 * Deletes a menu item
 */
class Delete
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
        $menuId = (int)$this->request->getParam('menu_id');
        
        try {
            $itemId = (int)$this->request->getParam('id');
            
            if (!$itemId) {
                throw new \RuntimeException('Menu item ID is required');
            }
            
            $this->menuItemRepository->delete($itemId);
            
            $this->messageManager->addSuccessMessage('Menu item deleted successfully');
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Error deleting menu item: ' . $e->getMessage());
        }
        
        return $this->response->setRedirect('/admin/menu/menuitem/index?menu_id=' . $menuId);
    }
}
