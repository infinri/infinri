<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Core\Model\Message\MessageManager;

/**
 * Menu Delete Controller
 * 
 * Deletes a menu
 */
class Delete extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard,
        private readonly MenuRepository $menuRepository,
        private readonly MessageManager $messageManager
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        try {
            $menuId = $this->getIntParam('id');
            
            if (!$menuId) {
                throw new \RuntimeException('Menu ID is required');
            }
            
            $this->menuRepository->delete($menuId);
            $this->messageManager->addSuccess('Menu deleted successfully');
            
        } catch (\Exception $e) {
            $this->messageManager->addError('Error deleting menu: ' . $e->getMessage());
        }
        
        return $this->redirect('/admin/menu/menu/index');
    }
}
