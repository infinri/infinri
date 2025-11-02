<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\App\Request;
use Infinri\Cms\Controller\Adminhtml\AbstractSaveController;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Helper\ContentSanitizer;

/**
 * Handles POST request to save page data.
 */
class Save extends AbstractSaveController
{
    /**
     * @param PageRepository $pageRepository
     * @param ContentSanitizer $contentSanitizer
     */
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly ContentSanitizer $contentSanitizer,
        CsrfGuard $csrfGuard
    ) {
        parent::__construct($csrfGuard);
    }

    // ==================== REQUIRED ABSTRACT METHODS ====================

    /**
     * Get repository instance (implements abstract method)
     *
     * @return PageRepository
     */
    protected function getRepository(): PageRepository
    {
        return $this->pageRepository;
    }

    /**
     * @return string
     */
    protected function getIdParam(): string
    {
        return 'page_id';
    }

    /**
     * Get index route (implements abstract method)
     *
     * @return string
     */
    protected function getIndexRoute(): string
    {
        return '/admin/cms/page/index';
    }

    /**
     * @return string
     */
    protected function getEditRoute(): string
    {
        return '/admin/cms/page/edit';
    }

    /**
     * @return string
     */
    protected function getEntityName(): string
    {
        return 'page';
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractEntityData(Request $request): array
    {
        $content = $request->getParam('content', '');
        
        // 🔒 SECURITY: Sanitize HTML content on SAVE to prevent XSS
        // Using 'rich' profile to allow formatting while blocking dangerous elements
        if (!empty($content)) {
            $content = $this->contentSanitizer->sanitize($content, 'rich');
        }
        
        return [
            'title' => $request->getParam('title', ''),
            'url_key' => $request->getParam('url_key', ''),
            'content' => $content, // Sanitized content
            'meta_title' => $request->getParam('meta_title', ''),
            'meta_description' => $request->getParam('meta_description', ''),
            'meta_keywords' => $request->getParam('meta_keywords', ''),
            'is_active' => (bool) $request->getParam('is_active', false),
        ];
    }

    /**
     * @param array $data
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateRequiredFields(array $data): void
    {
        if (empty($data['title']) || empty($data['url_key'])) {
            throw new \InvalidArgumentException('Title and URL Key are required');
        }
    }
}
