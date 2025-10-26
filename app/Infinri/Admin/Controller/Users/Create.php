<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Users;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiFormRenderer;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Admin User Create Controller
 * Route: admin/users/create
 */
class Create
{
    public function __construct(
        private readonly UiFormRenderer $formRenderer,
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        // Render the form using UiFormRenderer (generates complete page)
        $formHtml = $this->formRenderer->render('admin_user_form');

        return (new Response())->setBody($formHtml);
    }
}
