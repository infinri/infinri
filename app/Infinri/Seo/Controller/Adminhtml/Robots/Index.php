<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Robots;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Robots.txt Management Controller
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
        $html = $this->layoutFactory->render('seo_adminhtml_robots_index');
        return (new Response())->setBody($html);
    }
}
