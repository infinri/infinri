<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Cms\Model\Repository\AbstractContentRepository;

/**
 * Base controller for saving CMS content entities (Page, Block, etc.)
 * @package Infinri\Cms\Controller\Adminhtml
 */
abstract class AbstractSaveController
{
    private const CSRF_FIELD = '_csrf_token';

    public function __construct(
        private readonly CsrfGuard $csrfGuard
    ) {
    }

    // ==================== ABSTRACT METHODS ====================

    /**
     * Each child controller provides its specific repository
     *
     * @return AbstractContentRepository
     */
    abstract protected function getRepository(): AbstractContentRepository;

    /**
     * @return string
     */
    abstract protected function getIdParam(): string;

    /**
     * Each controller defines which fields to extract
     *
     * @param Request $request
     * @return array
     */
    abstract protected function extractEntityData(Request $request): array;

    /**
     * @return string
     */
    abstract protected function getIndexRoute(): string;

    /**
     * @return string
     */
    abstract protected function getEditRoute(): string;

    /**
     * @return string
     */
    abstract protected function getEntityName(): string;

    /**
     * Override to add custom validation before save
     *
     * @param array $data
     * @return void
     * @throws \InvalidArgumentException
     */
    abstract protected function validateRequiredFields(array $data): void;

    // ==================== COMMON SAVE LOGIC ====================

    /**
     * Execute save action
     * Common logic for all save controllers
     *
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request): Response
    {
        $response = new Response();

        try {
            if (!$this->isValidCsrf($request)) {
                $response->setForbidden();
                $response->setBody('403 Forbidden - Invalid or missing CSRF token');
                return $response;
            }

            // Get entity ID (0 for new entities)
            $entityId = (int) $request->getParam($this->getIdParam(), 0);

            // Extract data from request
            $data = $this->extractEntityData($request);

            // Validate required fields
            $this->validateRequiredFields($data);

            // Load existing entity or create new one
            if ($entityId) {
                $entity = $this->getRepository()->getById($entityId);
                if (!$entity) {
                    throw new \RuntimeException(
                        ucfirst($this->getEntityName()) . " with ID {$entityId} not found"
                    );
                }
            } else {
                $entity = $this->getRepository()->create();
            }

            // Set data on entity
            foreach ($data as $key => $value) {
                $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                }
            }

            // Save entity
            $savedEntity = $this->getRepository()->save($entity);

            // Determine redirect based on button clicked
            if ($request->getParam('save_and_continue')) {
                // Save & Continue Edit button
                $response->setRedirect($this->getEditRoute() . '?id=' . $savedEntity->getId());
            } else {
                // Save button
                $response->setRedirect($this->getIndexRoute());
            }

        } catch (\Throwable $e) {
            $response->setServerError();
            $response->setBody('500 Internal Server Error - ' . $e->getMessage());
        }

        return $response;
    }

    protected function getCsrfTokenId(): string
    {
        return 'admin_cms_' . $this->getEntityName() . '_form';
    }

    private function isValidCsrf(Request $request): bool
    {
        $token = $request->getParam(self::CSRF_FIELD);
        return $this->csrfGuard->validateToken($this->getCsrfTokenId(), is_string($token) ? $token : null);
    }
}
