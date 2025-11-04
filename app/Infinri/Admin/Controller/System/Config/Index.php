<?php

declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * System Configuration Controller.
 */
class Index extends AbstractAdminController
{
    /**
     * Display configuration page.
     */
    public function execute(): Response
    {
        return $this->renderAdminLayout('admin_system_config_index');
    }
}
