<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Delete Controller
 */
class Delete extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request              $request,
        \Infinri\Core\App\Response             $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard       $csrfGuard,
        private RedirectManager                $redirectManager,
        private LoggerInterface                $logger
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        $redirectId = $this->getIntParam('id');

        if (!$redirectId) {
            return $this->response->setStatusCode(400)->setBody('Redirect ID is required');
        }

        try {
            $result = $this->redirectManager->deleteRedirect($redirectId);

            if ($result) {
                return $this->redirect('/admin/seo/redirect');
            }

            return $this->response->setStatusCode(404)->setBody('Redirect not found');

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete redirect', [
                'redirect_id' => $redirectId,
                'error' => $e->getMessage()
            ]);

            return $this->response->setStatusCode(500)->setBody('Failed to delete redirect: ' . $e->getMessage());
        }
    }
}
