<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\User;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Core\Helper\Logger;

/**
 * Admin User Delete Controller
 */
class Delete
{
    public function __construct(
        private readonly AdminUser $adminUserResource
    ) {
    }

    public function execute(Request $request): Response
    {
        $userId = (int)$request->getParam('id');
        
        if (!$userId) {
            return (new Response())->setRedirect('/admin/user/user/index');
        }
        
        try {
            // Prevent deleting yourself
            if (isset($_SESSION['admin_user_id']) && $_SESSION['admin_user_id'] == $userId) {
                throw new \RuntimeException('You cannot delete your own account');
            }
            
            // Check if this is the last admin
            $allUsers = $this->adminUserResource->findAll();
            $activeAdmins = array_filter($allUsers, function($user) {
                if (!$user['is_active']) return false;
                $roles = json_decode($user['roles'], true);
                return in_array('ROLE_ADMIN', $roles ?? []);
            });
            
            if (count($activeAdmins) === 1) {
                $user = $this->adminUserResource->load($userId);
                $roles = json_decode($user['roles'] ?? '[]', true);
                if (in_array('ROLE_ADMIN', $roles)) {
                    throw new \RuntimeException('Cannot delete the last active administrator');
                }
            }
            
            $this->adminUserResource->delete($userId);
            
            Logger::info('Admin user deleted', ['user_id' => $userId]);
            
        } catch (\Exception $e) {
            Logger::error('Failed to delete admin user', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
        }
        
        return (new Response())->setRedirect('/admin/user/user/index');
    }
}
