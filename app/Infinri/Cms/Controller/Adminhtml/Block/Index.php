<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiComponentRenderer;
use Infinri\Cms\Helper\AdminLayout;

/**
 * Displays CMS blocks using UI Component grid
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
        $gridHtml = $this->uiComponentRenderer->render('cms_block_listing');
        $html = $this->adminLayout->wrapContent($gridHtml, 'CMS Blocks');

        return (new Response())->setBody($html);
    }
}
