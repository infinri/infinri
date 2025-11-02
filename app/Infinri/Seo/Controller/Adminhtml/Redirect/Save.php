<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Save Controller
 */
class Save
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

        $data = $request->getPost();
        $redirectId = isset($data['redirect_id']) ? (int)$data['redirect_id'] : null;

        try {
            // Validate data
            $errors = $this->redirectManager->validateRedirectData($data);
            if (!empty($errors)) {
                $response->setStatusCode(400);
                return $response->setBody('Validation failed: ' . implode(', ', $errors));
            }

            // Save redirect
            if ($redirectId) {
                // Update existing redirect
                $redirect = $this->redirectManager->updateRedirect($redirectId, $data);
                $message = 'Redirect updated successfully.';
            } else {
                // Create new redirect
                $redirect = $this->redirectManager->createRedirect(
                    $data['from_path'],
                    $data['to_path'],
                    (int)($data['redirect_code'] ?? 301),
                    $data['description'] ?? null,
                    isset($data['is_active']) ? (bool)$data['is_active'] : true,
                    (int)($data['priority'] ?? 0)
                );
                $message = 'Redirect created successfully.';
            }

            // Determine redirect location
            if (isset($data['back']) && $data['back'] === 'continue') {
                // Save & Continue - redirect to edit page
                $redirectUrl = '/admin/seo/redirect/edit?id=' . $redirect->getRedirectId();
            } else {
                // Save - redirect to grid
                $redirectUrl = '/admin/seo/redirect';
            }

            $response->setStatusCode(302);
            $response->setHeader('Location', $redirectUrl);
            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Failed to save redirect', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            $response->setStatusCode(500);
            return $response->setBody('Failed to save redirect: ' . $e->getMessage());
        }
    }
}
