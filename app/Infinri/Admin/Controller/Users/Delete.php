<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\Repository\AdminUserRepository;

/**
 * Admin User Delete Controller
 * Route: admin/users/delete
 */
class Delete extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard,
        private readonly AdminUserRepository $repository
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        $userId = $this->getIntParam('id');

        if (!$userId) {
            Logger::error('Delete user: No user ID provided');
            return $this->redirectWithError('/admin/users/index');
        }

        try {
            $user = $this->repository->getById($userId);

            if (!$user) {
                Logger::error('Delete user: User not found', ['user_id' => $userId]);
                return $this->redirectWithError('/admin/users/index');
            }

            // Delete the user via repository
            $this->repository->delete($user);
            
            Logger::info('User deleted successfully', [
                'user_id' => $userId,
                'username' => $user->getUsername()
            ]);

            return $this->redirectWithSuccess('/admin/users/index');

        } catch (\Exception $e) {
            Logger::error('Delete user failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return $this->redirectWithError('/admin/users/index');
        }
    }
}
