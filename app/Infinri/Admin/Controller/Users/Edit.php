<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Admin User Edit Controller
 * Route: admin/users/edit
 * Uses layout system with UI Component form
 */
class Edit
{
    public function __construct(
        private readonly AdminUser $adminUser,
        private readonly AdminUserResource $adminUserResource,
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        $userId = (int) $request->getParam('id');
        
        if (!$userId) {
            Logger::error('Edit user: No user ID provided');
            $response = new Response();
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index');
            return $response;
        }

        try {
            $userData = $this->adminUserResource->load($userId);

            if (!$userData) {
                Logger::error("Edit user: User not found", ['user_id' => $userId]);
                $response = new Response();
                $response->setStatusCode(302);
                $response->setHeader('Location', '/admin/users/index');
                return $response;
            }

            // Render using layout system (proper separation of concerns)
            // Pass 'id' parameter (standard for UI Component forms)
            $html = $this->layoutFactory->render('admin_users_edit', [
                'id' => $userId
            ]);

            return (new Response())->setBody($html);
            
        } catch (\Exception $e) {
            Logger::error('Edit user failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            $response = new Response();
            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/users/index');
            return $response;
        }
    }

}
