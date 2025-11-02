<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;

/**
 * System Configuration Controller
 */
class Index extends AbstractAdminController
{
    /**
     * Display configuration page
     */
    public function execute(): Response
    {
        return $this->renderAdminLayout('admin_system_config_index');
    }
}
