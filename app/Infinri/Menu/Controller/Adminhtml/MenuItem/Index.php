<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\MenuItem;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Menu\Model\Repository\MenuRepository;

/**
 * Menu Item Grid Controller
 * 
 * Displays the menu item listing grid for a specific menu
 */
class Index
{
    /**
     * Constructor
     *
     * @param LayoutFactory $layoutFactory
     * @param MenuRepository $menuRepository
     */
    public function __construct(
        private readonly LayoutFactory $layoutFactory,
        private readonly MenuRepository $menuRepository
    ) {}

    /**
     * Execute action
     *
     * @return Response
     */
    public function execute(Request $request): Response
    {
        $menuId = (int)$request->getParam('menu_id');
        
        if (!$menuId) {
            return (new Response())->setRedirect('/admin/menu/menu/index');
        }
        
        $menu = $this->menuRepository->getById($menuId);
        
        if (!$menu) {
            return (new Response())->setRedirect('/admin/menu/menu/index');
        }
        
        $html = $this->layoutFactory->render('menu_adminhtml_menuitem_index');
        
        return (new Response())->setBody($html);
    }
}
