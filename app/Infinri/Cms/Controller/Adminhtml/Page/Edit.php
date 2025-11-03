<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * Edit/Create Page Controller
 * Route: admin/cms/page/edit?id={page_id}
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        $pageId = $this->getIntParam('id');

        return $this->renderAdminLayout('cms_adminhtml_page_edit', [
            'id' => $pageId ?: null
        ]);
    }
}
