<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiComponentRenderer;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Displays CMS pages using UI Component grid
 */
class Index
{
    public function __construct(
        private readonly UiComponentRenderer $uiComponentRenderer,
        private readonly AdminLayout $adminLayout
    ) {
    }

    public function execute(Request $request): Response
    {
        $gridHtml = $this->uiComponentRenderer->render('cms_page_listing');
        $html = $this->adminLayout->wrapContent($gridHtml, 'CMS Pages');
        
        return (new Response())->setBody($html);
    }
}
