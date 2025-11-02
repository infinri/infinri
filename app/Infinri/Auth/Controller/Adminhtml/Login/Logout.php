<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Core\Helper\Logger;
use Infinri\Core\Security\CsrfGuard;

/**
 * Admin Logout Controller
 * Route: /admin/auth/login/logout
 */
class Logout
{
    public function __construct(
        private readonly RememberTokenService $rememberTokenService,
        private readonly CsrfGuard $csrfGuard
    ) {
    }

    public function execute(Request $request): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 🔒 SECURITY: Logout must be POST with CSRF token to prevent CSRF attacks
        if (!$request->isPost()) {
            Logger::warning('Logout failed: Not a POST request');
            return $this->createRedirect('/admin/dashboard/index');
        }

        // Validate CSRF token
        $csrfToken = $request->getPost('_csrf_token', '');
        $csrfTokenId = $request->getPost('_csrf_token_id', 'admin_logout');

        if (!$this->csrfGuard->validateToken($csrfTokenId, $csrfToken)) {
            Logger::warning('Logout failed: Invalid CSRF token', [
                'token_id' => $csrfTokenId,
                'has_token' => !empty($csrfToken)
            ]);
            return $this->createRedirect('/admin/dashboard/index');
        }

        $username = $_SESSION['admin_username'] ?? 'unknown';

        Logger::info('Logout: Session before destroy', [
            'username' => $username,
            'session_data' => array_keys($_SESSION)
        ]);

        // Delete remember me cookie if exists
        $rememberToken = $this->rememberTokenService->getRememberCookie();
        if ($rememberToken) {
            $this->rememberTokenService->revokeToken($rememberToken);
            $this->rememberTokenService->deleteRememberCookie();
            Logger::info('Remember Me cookie deleted on logout');
        }

        // Unset all session variables
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        Logger::info('Admin logged out', ['username' => $username]);

        // Redirect to login page
        return $this->createRedirect('/admin/auth/login/index');
    }

    private function createRedirect(string $url): Response
    {
        $response = new Response();
        $response->setStatusCode(302);
        $response->setHeader('Location', $url);
        return $response;
    }
}
