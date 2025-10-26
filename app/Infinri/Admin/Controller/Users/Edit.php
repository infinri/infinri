<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Core\View\Element\UiFormRenderer;

/**
 * Admin User Edit Controller
 * Route: admin/users/edit
 */
class Edit
{
    public function __construct(
        private readonly AdminUser $adminUser,
        private readonly AdminUserResource $adminUserResource,
        private readonly LayoutFactory $layoutFactory,
        private readonly UiFormRenderer $formRenderer
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

            // Render the form using UiFormRenderer with user ID (generates complete page)
            $formHtml = $this->formRenderer->render('admin_user_form', [
                'id' => $userId
            ]);

            return (new Response())->setBody($formHtml);
            
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
