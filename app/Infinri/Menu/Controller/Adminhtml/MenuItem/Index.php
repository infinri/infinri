<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\MenuItem;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiComponentRenderer;
use Infinri\Menu\Model\Repository\MenuRepository;

/**
 * Menu Item Grid Controller
 * 
 * Displays the menu item listing grid for a specific menu
 */
class Index
{
    /**
     * Constructor
     *
     * @param Request $request
     * @param Response $response
     * @param UiComponentRenderer $uiComponentRenderer
     * @param MenuRepository $menuRepository
     */
    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        private readonly UiComponentRenderer $uiComponentRenderer,
        private readonly MenuRepository $menuRepository
    ) {}

    /**
     * Execute action
     *
     * @return Response
     */
    public function execute(): Response
    {
        $menuId = (int)$this->request->getParam('menu_id');
        
        if (!$menuId) {
            return $this->response->setRedirect('/admin/menu/menu/index');
        }
        
        $menu = $this->menuRepository->getById($menuId);
        
        if (!$menu) {
            return $this->response->setRedirect('/admin/menu/menu/index');
        }
        
        $gridHtml = $this->uiComponentRenderer->render('menu_item_listing');
        
        $title = 'Manage Menu Items: ' . $menu->getTitle();
        
        return $this->response->setBody($this->wrapInAdminLayout($gridHtml, $title));
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
