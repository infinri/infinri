<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Mass Delete Controller
 */
class MassDelete
{
    public function __construct(
        private RedirectManager $redirectManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Execute action
     */
    public function execute(Request $request, Response $response): Response
    {
        if ($request->getMethod() !== 'POST') {
            $response->setStatusCode(405);
            return $response->setBody('Method Not Allowed');
        }

        $ids = $request->getPost('ids', []);

        if (empty($ids)) {
            $response->setStatusCode(400);
            return $response->setBody('No redirects selected');
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

            $response->setStatusCode(302);
            $response->setHeader('Location', '/admin/seo/redirect');
            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to mass delete redirects', [
                'error' => $e->getMessage()
            ]);

            $response->setStatusCode(500);
            return $response->setBody('Failed to delete redirects: ' . $e->getMessage());
        }
    }
}
