<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Core\Model\Message\Manager as MessageManager;

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
     * @param Request $request
     * @param Response $response
     * @param MenuRepository $menuRepository
     * @param MessageManager $messageManager
     */
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly MenuRepository $menuRepository,
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
            $menuId = (int)$this->request->getParam('id');
            
            if (!$menuId) {
                throw new \RuntimeException('Menu ID is required');
            }
            
            $this->menuRepository->delete($menuId);
            
            $this->messageManager->addSuccessMessage('Menu deleted successfully');
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Error deleting menu: ' . $e->getMessage());
        }
        
        return $this->response->setRedirect('/admin/menu/menu/index');
    }
}
