<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiFormRenderer;

/**
 * Menu Edit Controller
 * 
 * Displays menu edit/create form
 */
class Edit
{
    /**
     * Constructor
     *
     * @param Request $request
     * @param Response $response
     * @param UiFormRenderer $uiFormRenderer
     */
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly UiFormRenderer $uiFormRenderer
    ) {}

    /**
     * Execute action
     *
     * @return Response
     */
    public function execute(): Response
    {
        $menuId = $this->request->getParam('id') ? (int)$this->request->getParam('id') : null;
        
        $formHtml = $this->uiFormRenderer->render('menu_form', $menuId);
        
        $title = $menuId ? 'Edit Menu' : 'New Menu';
        
        return $this->response->setBody($this->wrapInAdminLayout($formHtml, $title));
    }

    /**
     * Wrap content in admin layout
     *
     * @param string $content
     * @param string $title
     * @return string
     */
    private function wrapInAdminLayout(string $content, string $title): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Admin</title>
    <link rel="stylesheet" href="/static/adminhtml/css/styles.min.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <header class="admin-header">
            <h1>{$title}</h1>
        </header>
        <main class="admin-content">
            {$content}
        </main>
    </div>
    <script src="/static/adminhtml/js/admin.min.js"></script>
</body>
</html>
HTML;
    }
}
