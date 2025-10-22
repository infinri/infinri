<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\User;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Core\Helper\Logger;

/**
 * Admin User Save Controller
 */
class Save
{
    public function __construct(
        private readonly AdminUser $adminUserResource
    ) {
    }

    public function execute(Request $request): Response
    {
        $userId = (int)$request->getPost('user_id');
        
        try {
            // Prepare user data
            $userData = [
                'username' => $request->getPost('username'),
                'email' => $request->getPost('email'),
                'firstname' => $request->getPost('firstname'),
                'lastname' => $request->getPost('lastname'),
                'roles' => json_encode($request->getPost('roles', ['ROLE_USER'])),
                'is_active' => $request->getPost('is_active') === '1' ? 1 : 0,
            ];
            
            // Add password if provided
            $password = $request->getPost('password');
            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if ($userId) {
                // Update existing user
                $userData['user_id'] = $userId;
                $this->adminUserResource->save($userData);
                
                Logger::info('Admin user updated', ['user_id' => $userId]);
            } else {
                // Create new user
                if (empty($password)) {
                    throw new \RuntimeException('Password is required for new users');
                }
                
                // Check username uniqueness
                $existing = $this->adminUserResource->loadByUsername($userData['username']);
                if ($existing) {
                    throw new \RuntimeException('Username already exists');
                }
                
                // Check email uniqueness
                $existingEmail = $this->adminUserResource->loadByEmail($userData['email']);
                if ($existingEmail) {
                    throw new \RuntimeException('Email already exists');
                }
                
                $newUserId = $this->adminUserResource->save($userData);
                
                Logger::info('Admin user created', ['user_id' => $newUserId, 'username' => $userData['username']]);
            }
            
            return (new Response())->setRedirect('/admin/user/user/index');
            
        } catch (\Exception $e) {
            Logger::error('Failed to save admin user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            $error = urlencode($e->getMessage());
            $redirect = $userId ? "/admin/user/user/edit?id={$userId}&error={$error}" : "/admin/user/user/create?error={$error}";
            
            return (new Response())->setRedirect($redirect);
        }
    }
}
