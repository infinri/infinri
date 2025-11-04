<?php

declare(strict_types=1);

namespace Infinri\Admin\Controller\Dashboard;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Main landing page for admin panel.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('admin_dashboard_index');
    }
}
