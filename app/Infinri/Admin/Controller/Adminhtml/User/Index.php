<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\User;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiComponentRenderer;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Admin User List Controller
 * Displays admin users using UI Component grid
 */
class Index
{
    public function __construct(
        private readonly UiComponentRenderer $uiComponentRenderer,
        private readonly AdminLayout $adminLayout
    ) {
    }

    public function execute(Request $request): Response
    {
        $gridHtml = $this->uiComponentRenderer->render('admin_user_listing');
        $html = $this->adminLayout->wrapContent($gridHtml, 'Admin Users');
        
        return (new Response())->setBody($html);
    }
}
