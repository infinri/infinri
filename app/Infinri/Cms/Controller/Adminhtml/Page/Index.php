<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Displays CMS pages using UI Component grid.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('cms_adminhtml_page_index');
    }
}
