<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\User;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Admin User Edit Controller
 */
class Edit
{
    public function __construct(
        private readonly AdminUser $adminUserResource,
        private readonly AdminLayout $adminLayout
    ) {
    }

    public function execute(Request $request): Response
    {
        $userId = (int)$request->getParam('id');
        
        if (!$userId) {
            return (new Response())
                ->setRedirect('/admin/user/user/index')
                ->setBody('Invalid user ID');
        }
        
        $user = $this->adminUserResource->load($userId);
        
        if (!$user) {
            return (new Response())
                ->setRedirect('/admin/user/user/index')
                ->setBody('User not found');
        }
        
        $formHtml = $this->renderEditForm($user);
        $html = $this->adminLayout->wrapContent($formHtml, 'Edit Admin User');
        
        return (new Response())->setBody($html);
    }
    
    private function renderEditForm(array $user): string
    {
        $userId = htmlspecialchars((string)$user['user_id']);
        $username = htmlspecialchars($user['username']);
        $email = htmlspecialchars($user['email']);
        $firstname = htmlspecialchars($user['firstname'] ?? '');
        $lastname = htmlspecialchars($user['lastname'] ?? '');
        $isActive = $user['is_active'] ? 'checked' : '';
        
        $roles = json_decode($user['roles'], true) ?? [];
        $isAdmin = in_array('ROLE_ADMIN', $roles) ? 'checked' : '';
        $isUser = in_array('ROLE_USER', $roles) ? 'checked' : '';
        
        return <<<HTML
<div class="admin-grid-container">
    <form action="/admin/user/user/save" method="POST">
        <input type="hidden" name="user_id" value="{$userId}">
        
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" value="{$username}" readonly class="readonly">
            <small>Username cannot be changed</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" value="{$email}" required>
        </div>
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" value="{$firstname}" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" value="{$lastname}" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
            <small>Minimum 8 characters</small>
        </div>
        
        <div class="form-group">
            <label>Roles *</label>
            <div>
                <label><input type="checkbox" name="roles[]" value="ROLE_ADMIN" {$isAdmin}> Administrator</label><br>
                <label><input type="checkbox" name="roles[]" value="ROLE_USER" {$isUser}> User</label>
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" {$isActive}>
                Active
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button primary">Save User</button>
            <a href="/admin/user/user/index" class="button">Cancel</a>
        </div>
    </form>
</div>

<style>
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] { 
    width: 100%; 
    padding: 8px; 
    border: 1px solid #ddd; 
    border-radius: 4px; 
}
.form-group input.readonly { background: #f5f5f5; cursor: not-allowed; }
.form-group small { display: block; color: #666; margin-top: 4px; }
.form-actions { margin-top: 30px; }
.form-actions .button { margin-right: 10px; }
</style>
HTML;
    }
}
