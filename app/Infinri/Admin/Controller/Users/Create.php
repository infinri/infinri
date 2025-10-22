<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Admin User Create Controller
 * Route: admin/users/create
 */
class Create
{
    public function __construct(
        private readonly AdminLayout $adminLayout
    ) {
    }

    public function execute(Request $request): Response
    {
        // Render create form
        $formHtml = $this->renderCreateForm();
        $html = $this->adminLayout->wrapContent($formHtml, 'Create New Admin User');
        
        return (new Response())->setBody($html);
    }

    private function renderCreateForm(): string
    {
        // TODO: Implement proper form rendering with UI components
        return '<form method="post" action="/admin/users/save">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" checked>
                    Active
                </label>
            </div>
            <button type="submit">Create User</button>
            <a href="/admin/users/index">Cancel</a>
        </form>';
    }
}
