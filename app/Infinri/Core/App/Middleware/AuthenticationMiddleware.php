<?php
declare(strict_types=1);

namespace Infinri\Core\App\Middleware;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Core\Helper\Logger;

/**
 * Authentication Middleware
 * 
 * Protects admin routes by verifying session authentication or remember token
 */
class AuthenticationMiddleware
{
    public function __construct(
        private readonly RememberTokenService $rememberTokenService,
        private readonly AdminUserResource $adminUserResource
    ) {}
    /**
     * Routes that don't require authentication
     */
    private const PUBLIC_ROUTES = [
        '/admin/auth',
    ];

    /**
     * Handle authentication check
     */
    public function handle(Request $request, Response $response): Response
    {
        // Skip authentication in test environment
        if ($this->isTestEnvironment()) {
            return $response;
        }

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $path = $request->getPathInfo();

        // Skip authentication for public routes
        if ($this->isPublicRoute($path)) {
            return $response;
        }

        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            // Try to auto-login from remember token
            if ($this->attemptRememberMeLogin($request)) {
                Logger::info('Auto-login successful via Remember Me token');
                // Continue to requested page
                return $response;
            }
            
            Logger::warning('Unauthenticated access attempt', ['path' => $path]);
            
            // Redirect to login
            $response->setRedirect('/admin/auth/login/index?redirect=' . urlencode($path));
            return $response;
        }

        // Verify session fingerprint for security
        if (!$this->verifySessionFingerprint($request)) {
            Logger::warning('Session fingerprint mismatch - possible hijacking', [
                'path' => $path,
                'user_id' => $_SESSION['admin_user_id'] ?? null
            ]);
            
            // Destroy session and redirect to login
            $this->destroySession();
            $response->setRedirect('/admin/auth/login/index?error=session_invalid');
            return $response;
        }

        // Update session activity timestamp
        $_SESSION['admin_last_activity'] = time();

        return $response;
    }

    /**
     * Check if route is public (doesn't require auth)
     */
    private function isPublicRoute(string $path): bool
    {
        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if (str_starts_with($path, $publicRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is authenticated
     */
    private function isAuthenticated(): bool
    {
        $isAuth = isset($_SESSION['admin_authenticated']) 
            && $_SESSION['admin_authenticated'] === true
            && isset($_SESSION['admin_user_id']);
        
        Logger::debug('AuthMiddleware: Authentication check', [
            'is_authenticated' => $isAuth,
            'session_keys' => array_keys($_SESSION),
            'admin_authenticated' => $_SESSION['admin_authenticated'] ?? 'not set',
            'admin_user_id' => $_SESSION['admin_user_id'] ?? 'not set'
        ]);
        
        return $isAuth;
    }

    /**
     * Verify session fingerprint to detect hijacking
     */
    private function verifySessionFingerprint(Request $request): bool
    {
        if (!isset($_SESSION['admin_fingerprint'])) {
            return false;
        }

        $currentFingerprint = hash('sha256', 
            $request->getClientIp() . 
            $request->getUserAgent()
        );

        return hash_equals($_SESSION['admin_fingerprint'], $currentFingerprint);
    }

    /**
     * Destroy session
     */
    private function destroySession(): void
    {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Check if running in test environment
     */
    private function isTestEnvironment(): bool
    {
        return defined('PHPUNIT_RUNNING') 
            || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')
            || (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing');
    }

    /**
     * Attempt to auto-login user from remember token
     */
    private function attemptRememberMeLogin(Request $request): bool
    {
        // Get remember token from cookie
        $token = $this->rememberTokenService->getRememberCookie();
        
        if (!$token) {
            return false;
        }
        
        // Validate token and get user ID
        $userId = $this->rememberTokenService->validateToken($token);
        
        if (!$userId) {
            // Invalid token, delete cookie
            $this->rememberTokenService->deleteRememberCookie();
            return false;
        }
        
        // Load user data
        $userData = $this->adminUserResource->findOneBy(['user_id' => $userId]);
        
        if (!$userData || !$userData['is_active']) {
            Logger::warning('Remember Me token valid but user inactive/not found', ['user_id' => $userId]);
            $this->rememberTokenService->deleteRememberCookie();
            return false;
        }
        
        // Create session (auto-login)
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = $userData['user_id'];
        $_SESSION['admin_username'] = $userData['username'];
        $_SESSION['admin_email'] = $userData['email'];
        $_SESSION['admin_roles'] = json_decode($userData['roles'], true);
        $_SESSION['admin_full_name'] = ($userData['firstname'] ?? '') . ' ' . ($userData['lastname'] ?? '');
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_fingerprint'] = hash('sha256', $request->getClientIp() . $request->getUserAgent());
        
        Logger::info('User auto-logged in via Remember Me', [
            'user_id' => $userId,
            'username' => $userData['username']
        ]);
        
        return true;
    }
}
