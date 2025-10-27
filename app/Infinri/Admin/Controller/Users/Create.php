<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Admin User Create Controller
 * Route: admin/users/create
 * Uses layout system with UI Component form
 */
class Create
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        // Render using layout system (proper separation of concerns)
        $html = $this->layoutFactory->render('admin_users_create');

        return (new Response())->setBody($html);
    }
}
