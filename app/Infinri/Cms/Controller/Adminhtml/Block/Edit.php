<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Route: admin/cms/block/edit?id={block_id}
 * Uses layout system with UI Component form.
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        $blockId = $this->getIntParam('id');

        return $this->renderAdminLayout('cms_adminhtml_block_edit', [
            'id' => $blockId ?: null,
        ]);
    }
}
