<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;

/**
 * Media Picker Controller.
 */
class Picker extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        $csrfToken = $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD);

        $templatePath = \dirname(__DIR__, 3) . '/view/adminhtml/templates/media/picker.phtml';

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $html = ob_get_clean();
            if (false === $html) {
                $html = '<p>Error: Failed to capture template output</p>';
            }
        } else {
            $html = '<p>Error: Picker template not found</p>';
        }

        return $this->response->setBody($html);
    }
}
