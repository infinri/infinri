<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Core\Security\CsrfTokenManager;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login POST Handler
 * 
 * Processes login form submission with CSRF protection and Remember Me
 */
class Post extends AbstractController
{
    public function __construct(
        Request $request,
        Response $response,
        private readonly AdminUserResource $adminUserResource,
        private readonly CsrfTokenManager $csrfManager,
        private readonly RememberTokenService $rememberTokenService
    ) {
        parent::__construct($request, $response);
    }

    public function execute(): Response
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate CSRF token
        $csrfToken = $this->request->getPost('_csrf_token', '');
        $csrfTokenId = $this->request->getPost('_csrf_token_id', 'admin_login');

        if (!$this->csrfManager->validateToken($csrfTokenId, $csrfToken)) {
            Logger::warning('Login failed: Invalid CSRF token', [
                'ip' => $this->request->getClientIp()
            ]);
            return $this->redirect('/admin/auth/login/index?error=csrf');
        }

        $username = $this->request->getPost('username', '');
        $password = $this->request->getPost('password', '');

        Logger::info('Admin login attempt', ['username' => $username]);

        // Validate input
        if (empty($username) || empty($password)) {
            Logger::warning('Login failed: Empty credentials');
            return $this->redirect('/admin/auth/login?error=empty');
        }

        // Load user
        $userData = $this->adminUserResource->loadByUsername($username);

        if (!$userData) {
            Logger::warning('Login failed: User not found', ['username' => $username]);
            usleep(random_int(100000, 500000)); // Timing attack prevention
            return $this->redirect('/admin/auth/login?error=invalid');
        }

        // Create user model
        $user = new AdminUser($this->adminUserResource);
        $user->setData($userData);

        // Check active status
        if (!$user->isActive()) {
            Logger::warning('Login failed: User inactive', ['username' => $username]);
            return $this->redirect('/admin/auth/login?error=inactive');
        }

        // Verify password
        if (!password_verify($password, $user->getPassword())) {
            Logger::warning('Login failed: Invalid password', ['username' => $username]);
            usleep(random_int(100000, 500000)); // Timing attack prevention
            return $this->redirect('/admin/auth/login?error=invalid');
        }

        // Handle Remember Me BEFORE session operations (must be before headers are sent)
        $rememberMe = $this->request->getPost('remember_me', '0');
        if ($rememberMe === '1') {
            $token = $this->rememberTokenService->generateToken(
                $user->getUserId(),
                $this->request->getClientIp(),
                $this->request->getUserAgent()
            );
            
            $this->rememberTokenService->setRememberCookie($token);
            Logger::info('Remember Me cookie set', ['user_id' => $user->getUserId()]);
        }

        // Create session
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $user->getUserId();
        $_SESSION['admin_username'] = $user->getUsername();
        $_SESSION['admin_email'] = $user->getEmail();
        $_SESSION['admin_roles'] = $user->getRoles();
        $_SESSION['admin_full_name'] = $user->getFullName();
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_fingerprint'] = $this->getFingerprint();

        // Update last login
        $this->adminUserResource->updateLastLogin($user->getUserId());

        Logger::info('Admin login successful', [
            'user_id' => $user->getUserId(),
            'username' => $username,
            'remember_me' => $rememberMe === '1'
        ]);

        // Redirect to dashboard
        return $this->redirect('/admin/infinri');
    }

    private function getFingerprint(): string
    {
        return hash('sha256', 
            $this->request->getClientIp() . 
            $this->request->getUserAgent()
        );
    }
}
