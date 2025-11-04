<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Sitemap;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Sitemap Management Controller.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_sitemap_index');
    }
}
