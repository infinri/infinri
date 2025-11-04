<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Robots;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Robots.txt Management Controller.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_robots_index');
    }
}
