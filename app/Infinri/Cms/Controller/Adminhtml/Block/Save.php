<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\App\Request;
use Infinri\Cms\Controller\Adminhtml\AbstractSaveController;
use Infinri\Cms\Model\Repository\BlockRepository;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Helper\ContentSanitizer;

/**
 * Handles POST request to save block data.
 */
class Save extends AbstractSaveController
{
    /**
     * @param BlockRepository $blockRepository
     * @param ContentSanitizer $contentSanitizer
     * @param CsrfGuard $csrfGuard
     */
    public function __construct(
        private readonly BlockRepository  $blockRepository,
        private readonly ContentSanitizer $contentSanitizer,
        CsrfGuard                         $csrfGuard
    ) {
        parent::__construct($csrfGuard);
    }

    /**
     * @return BlockRepository
     */
    protected function getRepository(): BlockRepository
    {
        return $this->blockRepository;
    }

    /**
     * @return string
     */
    protected function getIdParam(): string
    {
        return 'block_id';
    }

    /**
     * @return string
     */
    protected function getIndexRoute(): string
    {
        return '/admin/cms/block/index';
    }

    /**
     * @return string
     */
    protected function getEditRoute(): string
    {
        return '/admin/cms/block/edit';
    }

    /**
     * @return string
     */
    protected function getEntityName(): string
    {
        return 'block';
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractEntityData(Request $request): array
    {
        $content = $request->getParam('content', '');

        // Using 'rich' profile to allow formatting while blocking dangerous elements
        if (!empty($content)) {
            $content = $this->contentSanitizer->sanitize($content, 'rich');
        }

        return [
            'title' => $request->getParam('title', ''),
            'identifier' => $request->getParam('identifier', ''),
            'content' => $content, // Sanitized content
            'is_active' => (bool)$request->getParam('is_active', false),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateRequiredFields(array $data): void
    {
        if (empty($data['title']) || empty($data['identifier'])) {
            throw new \InvalidArgumentException('Title and Identifier are required');
        }
    }
}
