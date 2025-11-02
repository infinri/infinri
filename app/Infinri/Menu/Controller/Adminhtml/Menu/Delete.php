<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Core\Model\Message\MessageManager;

/**
 * Menu Delete Controller
 * 
 * Deletes a menu
 */
class Delete
{
    /**
     * Constructor
     *
     * @param MenuRepository $menuRepository
     * @param MessageManager $messageManager
     */
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly MessageManager $messageManager
    ) {}

    /**
     * Execute action
     *
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request): Response
    {
        try {
            $menuId = (int)$request->getParam('id');
            
            if (!$menuId) {
                throw new \RuntimeException('Menu ID is required');
            }
            
            $this->menuRepository->delete($menuId);
            
            $this->messageManager->addSuccess('Menu deleted successfully');
            
        } catch (\Exception $e) {
            $this->messageManager->addError('Error deleting menu: ' . $e->getMessage());
        }
        
        return (new Response())->setRedirect('/admin/menu/menu/index');
    }
}
