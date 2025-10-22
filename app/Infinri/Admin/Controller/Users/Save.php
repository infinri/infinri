<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;

/**
 * Admin User Save Controller
 * Route: admin/users/save
 */
class Save
{
    public function __construct(
        private readonly AdminUser $adminUser,
        private readonly AdminUserResource $adminUserResource
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

        try {
            $userId = (int) $request->getParam('user_id');
            $user = clone $this->adminUser;
            
            if ($userId) {
                $userData = $this->adminUserResource->load($userId);
                if (!$userData) {
                    throw new \RuntimeException('User not found');
                }
                $user->setData($userData);
            }

            // Set user data
            $user->setUsername($request->getParam('username'));
            $user->setEmail($request->getParam('email'));
            $user->setFirstname($request->getParam('firstname'));
            $user->setLastname($request->getParam('lastname'));
            
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
            $user->setIsActive((bool) $request->getParam('is_active', true));

            // Save user
            $user->save();
            
            Logger::info('User saved successfully', [
                'user_id' => $user->getUserId(),
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
