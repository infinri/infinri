<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\View\Element\UiFormRenderer;

/**
 * Abstract Edit Controller
 * 
 * Base controller for edit/create forms for CMS content entities
 * Provides common form rendering logic with minimal code duplication
 * Follows Magento pattern: edit?id=123 (edit) or edit (new)
 * 
 * @package Infinri\Cms\Controller\Adminhtml
 */
abstract class AbstractEditController
{
    /**
     * Constructor
     *
     * @param UiFormRenderer $formRenderer
     */
    public function __construct(
        protected readonly UiFormRenderer $formRenderer
    ) {
    }

    // ==================== ABSTRACT METHODS ====================

    /**
     * Get form name (e.g., 'cms_page_form', 'cms_block_form')
     *
     * @return string
     */
    abstract protected function getFormName(): string;

    /**
     * Get ID parameter name from request (e.g., 'id', 'page_id', 'block_id')
     *
     * @return string
     */
    abstract protected function getIdParam(): string;

    // ==================== COMMON EDIT LOGIC ====================

    /**
     * Execute edit action
     * Common logic for all edit controllers
     *
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request): Response
    {
        $response = new Response();

        try {
            // Get ID from request (null for new entity)
            $id = $request->getParam($this->getIdParam());
            $id = $id ? (int)$id : null;

            // Render form using UI Component
            $formHtml = $this->formRenderer->render($this->getFormName(), [
                'id' => $id,
            ]);

            $response->setBody($formHtml);

        } catch (\Throwable $e) {
            $response->setServerError();
            $response->setBody(
                '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>'
            );
        }

        return $response;
    }
}
