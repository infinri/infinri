<?php

declare(strict_types=1);

namespace Infinri\Menu\Controller\Adminhtml\Menu;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Displays menu edit/create form.
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('menu_adminhtml_menu_edit', [
            'id' => $this->request->getParam('id'),
        ]);
    }
}
