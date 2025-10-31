<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Edit/Create Block Controller
 * Route: admin/cms/block/edit?id={block_id}
 * Uses layout system with UI Component form
 * Follows Magento pattern: edit?id=123 (edit) or edit (new)
 */
class Edit
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {
    }

    public function execute(Request $request): Response
    {
        $blockId = (int) $request->getParam('id');
        
        // Render using layout system (proper separation of concerns)
        // Pass 'id' parameter (standard for UI Component forms)
        $html = $this->layoutFactory->render('cms_adminhtml_block_edit', [
            'id' => $blockId ?: null
        ]);

        return (new Response())->setBody($html);
    }
}
