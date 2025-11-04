<?php

declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login Page Controller
 * Route: /admin/auth/login/index.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        if (\PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        if (isset($_SESSION['admin_authenticated']) && true === $_SESSION['admin_authenticated']) {
            Logger::info('Admin already authenticated, redirecting to dashboard');

            return $this->redirect('/admin/dashboard/index');
        }

        return $this->renderAdminLayout('auth_adminhtml_login_index', [
            'error' => $this->getStringParam('error'),
        ]);
    }
}
