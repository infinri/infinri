<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Admin User Edit Controller
 * Route: admin/users/edit
 */
class Edit
{
    public function __construct(
        private readonly AdminUser $adminUser,
        private readonly AdminUserResource $adminUserResource,
        private readonly AdminLayout $adminLayout
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
            
            $user = clone $this->adminUser;
            $user->setData($userData);

            // Render edit form
            $formHtml = $this->renderEditForm($user);
            $html = $this->adminLayout->wrapContent($formHtml, 'Edit Admin User');
            
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

    private function renderEditForm($user): string
    {
        // TODO: Implement proper form rendering with UI components
        return '<form method="post" action="/admin/users/save">
            <input type="hidden" name="user_id" value="' . htmlspecialchars((string)$user->getUserId()) . '">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="' . htmlspecialchars($user->getUsername() ?? '') . '" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="' . htmlspecialchars($user->getEmail() ?? '') . '" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" value="' . htmlspecialchars($user->getFirstname() ?? '') . '" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" value="' . htmlspecialchars($user->getLastname() ?? '') . '" required>
            </div>
            <button type="submit">Save</button>
            <a href="/admin/users/index">Cancel</a>
        </form>';
    }
}
