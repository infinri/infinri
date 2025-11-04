<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Displays the menu listing grid.
 */
class Index extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('menu_adminhtml_menu_index');
    }
}
