<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Redirect Edit Controller.
 */
class Edit extends AbstractAdminController
{
    public function execute(): Response
    {
        return $this->renderAdminLayout('seo_adminhtml_redirect_edit', [
            'id' => $this->getIntParam('id') ?: null,
        ]);
    }
}
