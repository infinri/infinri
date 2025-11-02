<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\Logger;
use Infinri\Admin\Model\Repository\AdminUserRepository;

/**
 * Admin User Save Controller
 * Route: admin/users/save
 */
class Save extends AbstractAdminController
{
    private const CSRF_TOKEN_ID = 'admin_cms_user_form';
    
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
        // Require POST request
        if ($postError = $this->requirePost('/admin/users/index')) {
            return $postError;
        }
        
        // 🔒 SECURITY: Validate CSRF token before processing user save
        if ($csrfError = $this->requireCsrf(self::CSRF_TOKEN_ID, $this->getCsrfTokenFromRequest())) {
            return $csrfError;
        }

        try {
            $userId = $this->getIntParam('user_id');
            
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
            $user->setUsername($this->getStringParam('username'));
            $user->setEmail($this->getStringParam('email'));
            $user->setData('firstname', $this->getStringParam('firstname'));
            $user->setData('lastname', $this->getStringParam('lastname'));
            
            // Hash password if provided
            $password = $this->getStringParam('password');
            if (!empty($password)) {
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
            }
            
            // Set roles (default to ROLE_ADMIN if not provided)
            $roles = $this->request->getParam('roles');
            if (!empty($roles)) {
                if (is_string($roles)) {
                    $roles = json_decode($roles, true) ?: [$roles];
                }
            } else {
                $roles = ['ROLE_ADMIN'];
            }
            $user->setRoles($roles);
            
            // Set is_active
            $user->setData('is_active', $this->getBoolParam('is_active', true));

            // Save user via repository
            $this->repository->save($user);
            
            Logger::info('User saved successfully', [
                'user_id' => $user->getData('user_id'),
                'username' => $user->getUsername()
            ]);

            return $this->redirectWithSuccess('/admin/users/index');
            
        } catch (\Exception $e) {
            Logger::error('Save user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->redirectWithError('/admin/users/index');
        }
    }
}
