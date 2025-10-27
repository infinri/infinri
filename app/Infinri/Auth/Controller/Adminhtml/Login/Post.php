<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Login;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Core\Security\CsrfTokenManager;
use Infinri\Core\Helper\Logger;

/**
 * Admin Login POST Handler
 * Route: /admin/auth/login/post
 * Processes login form submission with CSRF protection and Remember Me
 */
class Post
{
    public function __construct(
        private readonly AdminUserResource $adminUserResource,
        private readonly CsrfTokenManager $csrfManager,
        private readonly RememberTokenService $rememberTokenService
    ) {
    }

    public function execute(Request $request): Response
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validate CSRF token
        $csrfToken = $request->getPost('_csrf_token', '');
        $csrfTokenId = $request->getPost('_csrf_token_id', 'admin_login');

        if (!$this->csrfManager->validateToken($csrfTokenId, $csrfToken)) {
            Logger::warning('Login failed: Invalid CSRF token', [
                'ip' => $request->getClientIp()
            ]);
            return $this->createRedirect('/admin/auth/login/index?error=csrf');
        }

        $username = $request->getPost('username', '');
        $password = $request->getPost('password', '');

        Logger::info('Admin login attempt', ['username' => $username]);

        // Validate input
        if (empty($username) || empty($password)) {
            Logger::warning('Login failed: Empty credentials');
            $_SESSION['login_error'] = 'Please enter both username and password.';
            $_SESSION['login_username'] = $username;
            return $this->createRedirect('/admin/auth/login/index');
        }

        // Load user
        $userData = $this->adminUserResource->loadByUsername($username);

        if (!$userData) {
            Logger::warning('Login failed: User not found', ['username' => $username]);
            usleep(random_int(100000, 500000)); // Timing attack prevention
            $_SESSION['login_error'] = 'Invalid username or password.';
            $_SESSION['login_username'] = $username;
            return $this->createRedirect('/admin/auth/login/index');
        }

        // Create user model
        $user = new AdminUser($this->adminUserResource);
        $user->setData($userData);

        // Check active status
        if (!$user->isActive()) {
            Logger::warning('Login failed: User inactive', ['username' => $username]);
            $_SESSION['login_error'] = 'This account has been disabled. Please contact an administrator.';
            $_SESSION['login_username'] = $username;
            return $this->createRedirect('/admin/auth/login/index');
        }

        // Verify password
        if (!password_verify($password, $user->getPassword())) {
            Logger::warning('Login failed: Invalid password', ['username' => $username]);
            usleep(random_int(100000, 500000)); // Timing attack prevention
            $_SESSION['login_error'] = 'Invalid username or password.';
            $_SESSION['login_username'] = $username;
            return $this->createRedirect('/admin/auth/login/index');
        }

        // Handle Remember Me BEFORE session operations (must be before headers are sent)
        $rememberMe = $request->getPost('remember_me', '0');
        if ($rememberMe === '1') {
            $token = $this->rememberTokenService->generateToken(
                $user->getUserId(),
                $request->getClientIp(),
                $request->getUserAgent()
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
        $_SESSION['admin_fingerprint'] = $this->getFingerprint($request);

        // Update last login
        $this->adminUserResource->updateLastLogin($user->getUserId());

        Logger::info('Admin login successful', [
            'user_id' => $user->getUserId(),
            'username' => $username,
            'remember_me' => $rememberMe === '1'
        ]);

        // Redirect to dashboard
        return $this->createRedirect('/admin/dashboard/index');
    }

    private function createRedirect(string $url): Response
    {
        $response = new Response();
        $response->setStatusCode(302);
        $response->setHeader('Location', $url);
        return $response;
    }

    private function getFingerprint(Request $request): string
    {
        return hash('sha256', 
            $request->getClientIp() . 
            $request->getUserAgent()
        );
    }
}
