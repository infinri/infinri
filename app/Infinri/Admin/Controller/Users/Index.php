<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Admin User List Controller
 * Route: admin/users/index
 * Displays admin users grid using UI Component and layout system
 */
class Index
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        // Render using layout system (proper separation of concerns)
        $html = $this->layoutFactory->render('admin_users_index');

        return (new Response())->setBody($html);
    }
}
