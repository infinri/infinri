<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Mass Delete Controller
 */
class MassDelete extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard,
        private RedirectManager $redirectManager,
        private LoggerInterface $logger
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        if ($postError = $this->requirePost('/admin/seo/redirect')) {
            return $this->response->setStatusCode(405)->setBody('Method Not Allowed');
        }

        $ids = $this->request->getPost('ids', []);

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setBody('No redirects selected');
        }

        try {
            $deleted = 0;
            foreach ($ids as $id) {
                if ($this->redirectManager->deleteRedirect((int)$id)) {
                    $deleted++;
                }
            }

            $this->logger->info('Mass delete redirects', [
                'deleted' => $deleted,
                'total' => count($ids)
            ]);

            return $this->redirect('/admin/seo/redirect');

        } catch (\Exception $e) {
            $this->logger->error('Failed to mass delete redirects', [
                'error' => $e->getMessage()
            ]);

            return $this->response->setStatusCode(500)->setBody('Failed to delete redirects: ' . $e->getMessage());
        }
    }
}
