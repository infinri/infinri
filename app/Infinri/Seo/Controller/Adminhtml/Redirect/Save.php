<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Adminhtml\Redirect;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Seo\Service\RedirectManager;
use Psr\Log\LoggerInterface;

/**
 * Redirect Save Controller.
 */
class Save extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        Response $response,
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

        $data = $this->request->getAllPost();
        $redirectId = isset($data['redirect_id']) ? (int) $data['redirect_id'] : null;

        try {
            $errors = $this->redirectManager->validateRedirectData($data);
            if (! empty($errors)) {
                return $this->response->setStatusCode(400)->setBody('Validation failed: ' . implode(', ', $errors));
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
                    (int) ($data['redirect_code'] ?? 301),
                    $data['description'] ?? null,
                    isset($data['is_active']) ? (bool) $data['is_active'] : true,
                    (int) ($data['priority'] ?? 0)
                );
                $message = 'Redirect created successfully.';
            }

            if (isset($data['back']) && 'continue' === $data['back']) {
                return $this->redirectToRoute('/admin/seo/redirect/edit', ['id' => $redirect->getRedirectId()]);
            }

            return $this->redirect('/admin/seo/redirect');
        } catch (\Exception $e) {
            $this->logger->error('Failed to save redirect', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return $this->response->setStatusCode(500)->setBody('Failed to save redirect: ' . $e->getMessage());
        }
    }
}
