<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Urlrewrite;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * URL Rewrite Management Controller
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_urlrewrite_index');
    }
}
