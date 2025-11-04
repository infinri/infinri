<?php

declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Index;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Auth Index Controller
 * Route: /admin/auth/index
 * Redirects to login page.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->redirect('/admin/auth/login/index');
    }
}
