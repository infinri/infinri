<?php

declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Admin User List Controller
 * Route: admin/users/index.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('admin_users_index');
    }
}
