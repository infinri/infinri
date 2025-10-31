<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Menu Edit Controller
 * 
 * Displays menu edit/create form
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
        $html = $this->layoutFactory->render('menu_adminhtml_menu_edit');
        
        return (new Response())->setBody($html);
    }
}
