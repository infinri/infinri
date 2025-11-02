<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Edit/Create User Controller
 * Route: admin/users/edit?id={user_id}
 * Uses layout system with UI Component form
 * Follows Magento pattern: edit?id=123 (edit) or edit (new)
 */
class Edit
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        $userId = (int) $request->getParam('id');
        
        // Render using layout system (proper separation of concerns)
        // Pass 'id' parameter (standard for UI Component forms)
        $html = $this->layoutFactory->render('admin_users_edit', [
            'id' => $userId ?: null
        ]);

        return (new Response())->setBody($html);
    }
}
