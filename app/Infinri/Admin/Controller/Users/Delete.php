<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\Repository\AdminUserRepository;

/**
 * Admin User Delete Controller
 * Route: admin/users/delete
 */
class Delete
{
    public function __construct(
        private readonly AdminUserRepository $repository
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
            $user = $this->repository->getById($userId);

            if (!$user) {
                Logger::error('Delete user: User not found', ['user_id' => $userId]);
                $response->setStatusCode(302);
                $response->setHeader('Location', '/admin/users/index?error=1');
                return $response;
            }

            // Delete the user via repository
            $this->repository->delete($user);
            
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
