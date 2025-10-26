<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Displays CMS pages using UI Component grid
 */
class Index
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        $html = $this->layoutFactory->render('cms_adminhtml_page_index');

        return (new Response())->setBody($html);
    }
}
