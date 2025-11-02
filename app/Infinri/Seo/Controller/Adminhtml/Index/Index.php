<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Index;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * SEO Dashboard Controller
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_index_index');
    }
}
