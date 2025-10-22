<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\User;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Admin User Create Controller
 */
class Create
{
    public function __construct(
        private readonly AdminLayout $adminLayout
    ) {
    }

    public function execute(Request $request): Response
    {
        $formHtml = $this->renderCreateForm();
        $html = $this->adminLayout->wrapContent($formHtml, 'Create New Admin User');
        
        return (new Response())->setBody($html);
    }
    
    private function renderCreateForm(): string
    {
        return <<<HTML
<div class="admin-grid-container">
    <form action="/admin/user/user/save" method="POST">
        
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_]+" minlength="3">
            <small>3-50 characters, alphanumeric and underscore only</small>
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="firstname">First Name *</label>
            <input type="text" id="firstname" name="firstname" required>
        </div>
        
        <div class="form-group">
            <label for="lastname">Last Name *</label>
            <input type="text" id="lastname" name="lastname" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required minlength="8">
            <small>Minimum 8 characters</small>
        </div>
        
        <div class="form-group">
            <label>Roles *</label>
            <div>
                <label><input type="checkbox" name="roles[]" value="ROLE_ADMIN"> Administrator</label><br>
                <label><input type="checkbox" name="roles[]" value="ROLE_USER" checked> User</label>
            </div>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button primary">Create User</button>
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
.form-group small { display: block; color: #666; margin-top: 4px; }
.form-actions { margin-top: 30px; }
.form-actions .button { margin-right: 10px; }
</style>
HTML;
    }
}
