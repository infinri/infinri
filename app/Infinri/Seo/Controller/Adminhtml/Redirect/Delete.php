<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Delete Controller
 */
class Delete
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
        $redirectId = $request->getParam('id');

        if (!$redirectId) {
            $response->setStatusCode(400);
            return $response->setBody('Redirect ID is required');
        }

        try {
            $result = $this->redirectManager->deleteRedirect((int)$redirectId);

            if ($result) {
                $response->setStatusCode(302);
                $response->setHeader('Location', '/admin/seo/redirect');
                return $response;
            } else {
                $response->setStatusCode(404);
                return $response->setBody('Redirect not found');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete redirect', [
                'redirect_id' => $redirectId,
                'error' => $e->getMessage()
            ]);

            $response->setStatusCode(500);
            return $response->setBody('Failed to delete redirect: ' . $e->getMessage());
        }
    }
}
