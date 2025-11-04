<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Index;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * SEO Dashboard Controller.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_index_index');
    }
}
