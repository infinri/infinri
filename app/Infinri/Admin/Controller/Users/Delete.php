<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;

/**
 * Admin User Delete Controller
 * Route: admin/users/delete
 */
class Delete
{
    public function __construct(
        private readonly AdminUser $adminUser,
        private readonly AdminUserResource $adminUserResource
    ) {
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        $userId = (int) $request->getParam('id');

        if (!$userId) {
            Logger::error('Delete user: No user ID provided');
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?error=1');
            return $response;
        }

        try {
            $userData = $this->adminUserResource->load($userId);

            if (!$userData) {
                Logger::error('Delete user: User not found', ['user_id' => $userId]);
                $response->setStatusCode(302);
                $response->setHeader('Location', '/admin/users/index?error=1');
                return $response;
            }
            
            $user = clone $this->adminUser;
            $user->setData($userData);

            // Delete the user
            $user->delete();
            
            Logger::info('User deleted successfully', [
                'user_id' => $userId,
                'username' => $user->getUsername()
            ]);

            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?success=1');
            return $response;

        } catch (\Exception $e) {
            Logger::error('Delete user failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index?error=1');
            return $response;
        }
    }
}
