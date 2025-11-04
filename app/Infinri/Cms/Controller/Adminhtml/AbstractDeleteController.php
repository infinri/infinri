<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml;

use Infinri\Cms\Model\Repository\AbstractContentRepository;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Base controller for deleting CMS content entities (Page, Block, etc.)
 * Provides common delete logic with minimal code duplication.
 */
abstract class AbstractDeleteController
{
    /**
     * Get repository instance
     * Each child controller provides its specific repository.
     */
    abstract protected function getRepository(): AbstractContentRepository;

    /**
     * Get ID parameter name from request (e.g., 'id', 'page_id', 'block_id').
     */
    abstract protected function getIdParam(): string;

    /**
     * Get index route for redirect after delete.
     */
    abstract protected function getIndexRoute(): string;

    /**
     * Get entity name for error messages (e.g., 'page', 'block').
     */
    abstract protected function getEntityName(): string;

    /**
     * Execute delete action
     * Common logic for all delete controllers.
     */
    public function execute(Request $request): Response
    {
        $response = new Response();

        try {
            $id = (int) $request->getParam($this->getIdParam(), 0);

            if (! $id) {
                throw new \InvalidArgumentException('Invalid ' . $this->getEntityName() . ' ID');
            }

            // Delete entity
            $this->getRepository()->delete($id);

            // Redirect to index
            $response->setRedirect($this->getIndexRoute());
        } catch (\Throwable $e) {
            $response->setServerError();
            $response->setBody('500 Internal Server Error - ' . $e->getMessage());
        }

        return $response;
    }
}
