<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\MenuItem;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Menu Item Edit Controller
 * 
 * Displays menu item edit/create form
 */
class Edit
{
    /**
     * Constructor
     *
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        private readonly LayoutFactory $layoutFactory
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
        
        $html = $this->layoutFactory->render('menu_adminhtml_menuitem_edit');
        
        return (new Response())->setBody($html);
    }
}
