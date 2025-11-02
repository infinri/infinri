<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Redirect Grid Controller
 */
class Index
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {}

    /**
     * Execute action
     */
    public function execute(Request $request): Response
    {
        $html = $this->layoutFactory->render('seo_adminhtml_redirect_index');
        
        return (new Response())->setBody($html);
    }
}
