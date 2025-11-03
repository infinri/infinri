<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login Page Controller
 * Route: /admin/auth/login/index
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
            Logger::info('Admin already authenticated, redirecting to dashboard');
            return $this->redirect('/admin/dashboard/index');
        }

        return $this->renderAdminLayout('auth_adminhtml_login_index', [
            'error' => $this->getStringParam('error')
        ]);
    }
}
