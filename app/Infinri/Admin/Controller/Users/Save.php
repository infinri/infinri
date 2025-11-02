<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\Repository\AdminUserRepository;
use Infinri\Core\Security\CsrfGuard;

/**
 * Admin User Save Controller
 * Route: admin/users/save
 */
class Save
{
    private const CSRF_TOKEN_ID = 'admin_cms_user_form';
    
    public function __construct(
        private readonly AdminUserRepository $repository,
        private readonly CsrfGuard $csrfGuard
    ) {
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        
        if (!$request->isPost()) {
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index');
            return $response;
        }
        
        // 🔒 SECURITY: Validate CSRF token before processing user save
        if (!$this->csrfGuard->validateToken(self::CSRF_TOKEN_ID, $request->getParam('_csrf_token'))) {
            Logger::warning('User save failed: Invalid CSRF token');
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?error=csrf');
            return $response;
        }

        try {
            $userId = (int) $request->getParam('user_id');
            
            // Load existing user or create new one
            if ($userId) {
                $user = $this->repository->getById($userId);
                if (!$user) {
                    throw new \RuntimeException('User not found');
                }
            } else {
                $user = $this->repository->create();
            }

            // Set user data
            $user->setUsername($request->getParam('username'));
            $user->setEmail($request->getParam('email'));
            $user->setData('firstname', $request->getParam('firstname'));
            $user->setData('lastname', $request->getParam('lastname'));
            
            // Hash password if provided
            $password = $request->getParam('password');
            if (!empty($password)) {
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
            }
            
            // Set roles (default to ROLE_ADMIN if not provided)
            $roles = $request->getParam('roles');
            if (!empty($roles)) {
                if (is_string($roles)) {
                    $roles = json_decode($roles, true) ?: [$roles];
                }
            } else {
                $roles = ['ROLE_ADMIN'];
            }
            $user->setRoles($roles);
            
            // Set is_active
            $user->setData('is_active', (bool) $request->getParam('is_active', true));

            // Save user via repository
            $this->repository->save($user);
            
            Logger::info('User saved successfully', [
                'user_id' => $user->getData('user_id'),
                'username' => $user->getUsername()
            ]);

            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?success=1');
            return $response;
            
        } catch (\Exception $e) {
            Logger::error('Save user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?error=1');
            return $response;
        }
    }
}
