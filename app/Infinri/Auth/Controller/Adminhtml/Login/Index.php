<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login Page Controller
 * Route: /admin/auth/login/index
 * Displays the login form using layout/template system
 */
class Index
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If already logged in, redirect to dashboard
        if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
            Logger::info('Admin already authenticated, redirecting to dashboard');
            
            $response = new Response();
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/dashboard/index');
            return $response;
        }

        // Render login page using layout
        $html = $this->layoutFactory->render('auth_adminhtml_login_index', [
            'error' => $request->getParam('error', '')
        ]);

        return (new Response())->setBody($html);
    }
}
