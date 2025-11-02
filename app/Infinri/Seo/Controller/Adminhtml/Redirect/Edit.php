<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Redirect Edit Controller
 */
class Edit
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {}

    /**
     * Execute action
     */
    public function execute(Request $request): Response
    {
        $redirectId = (int) $request->getParam('id');
        
        $html = $this->layoutFactory->render('seo_adminhtml_redirect_edit', [
            'id' => $redirectId ?: null
        ]);
        
        return (new Response())->setBody($html);
    }
}
