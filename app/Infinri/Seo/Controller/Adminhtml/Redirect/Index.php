<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * Redirect Grid Controller
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_redirect_index');
    }
}
