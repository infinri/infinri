<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * Edit/Create User Controller
 * Route: admin/users/edit?id={user_id}
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        $userId = $this->getIntParam('id');

        // Render using layout system (proper separation of concerns)
        return $this->renderAdminLayout('admin_users_edit', [
            'id' => $userId ?: null
        ]);
    }
}
