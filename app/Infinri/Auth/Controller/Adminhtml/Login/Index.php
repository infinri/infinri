<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login Page Controller
 * 
 * Displays the login form using layout/template system
 */
class Index extends AbstractController
{
    public function __construct(
        Request $request,
        Response $response,
        private readonly LayoutFactory $layoutFactory
    ) {
        parent::__construct($request, $response);
    }

    public function execute(): Response
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If already logged in, redirect to dashboard
        if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
            Logger::info('Admin already authenticated, redirecting to dashboard');
            return $this->redirect('/admin/infinri');
        }

        // Render login page using layout
        $html = $this->layoutFactory->render('auth_adminhtml_login_index', [
            'error' => $this->request->getParam('error', '')
        ]);

        return $this->response->setBody($html);
    }
}
