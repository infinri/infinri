<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Menu\Model\Repository\MenuRepository;
use Infinri\Core\Model\Message\Manager as MessageManager;

/**
 * Menu Save Controller
 * 
 * Saves menu data
 */
class Save
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
            $data = $this->request->getPostParams();
            
            if (empty($data)) {
                throw new \RuntimeException('No data provided');
            }
            
            // Create or update menu
            if (!empty($data['menu_id'])) {
                $menu = $this->menuRepository->getById((int)$data['menu_id']);
                if (!$menu) {
                    throw new \RuntimeException('Menu not found');
                }
            } else {
                $menu = $this->menuRepository->create();
            }
            
            $menu->setTitle($data['title'] ?? '');
            $menu->setIdentifier($data['identifier'] ?? '');
            $menu->setIsActive(!empty($data['is_active']));
            
            $this->menuRepository->save($menu);
            
            $this->messageManager->addSuccessMessage('Menu saved successfully');
            
            // Check if "Save & Continue"
            if ($this->request->getParam('back') === 'continue') {
                return $this->response->setRedirect('/admin/menu/menu/edit?id=' . $menu->getMenuId());
            }
            
            return $this->response->setRedirect('/admin/menu/menu/index');
            
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Error saving menu: ' . $e->getMessage());
            
            // Redirect back to form
            $menuId = $data['menu_id'] ?? null;
            $url = $menuId ? '/admin/menu/menu/edit?id=' . $menuId : '/admin/menu/menu/edit';
            
            return $this->response->setRedirect($url);
        }
    }
}
