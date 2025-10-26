<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Displays CMS blocks using UI Component grid
 */
class Index
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        // Render using layout system (Theme provides styling)
        $html = $this->layoutFactory->render('cms_adminhtml_block_index');

        return (new Response())->setBody($html);
    }
}
