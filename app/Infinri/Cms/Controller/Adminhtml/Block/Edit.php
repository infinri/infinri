<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * Edit/Create Block Controller
 * Route: admin/cms/block/edit?id={block_id}
 * Uses layout system with UI Component form
 * Follows Magento pattern: edit?id=123 (edit) or edit (new)
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        $blockId = $this->getIntParam('id');
        
        return $this->renderAdminLayout('cms_adminhtml_block_edit', [
            'id' => $blockId ?: null
        ]);
    }
}
