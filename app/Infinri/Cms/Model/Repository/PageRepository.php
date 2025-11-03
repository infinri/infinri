<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Api\PageRepositoryInterface;
use Infinri\Cms\Model\Page;
use Infinri\Cms\Model\ResourceModel\Page as PageResource;

/**
 * Provides CRUD operations for CMS pages with homepage protection.
 */
class PageRepository extends AbstractContentRepository implements PageRepositoryInterface
{
    /**
     * Constructor
     *
     * @param PageResource $resource
     */
    public function __construct(PageResource $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Create model instance (implements abstract method)
     *
     * @param array<string, mixed> $data
     * @return Page
     */
    protected function createModel(array $data = []): Page
    {
        return new Page($this->resource, $data);
    }

    /**
     * Get entity ID field name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityIdField(): string
    {
        return 'page_id';
    }

    /**
     * Create a new page instance
     * Public factory method for creating empty pages
     *
     * @param array<string, mixed> $data
     * @return Page
     */
    public function create(array $data = []): Page
    {
        return $this->createModel($data);
    }

    /**
     * Get page by ID (override with specific return type)
     *
     * @param int $id
     * @return Page|null
     */
    public function getById(int $id): ?Page
    {
        /** @var Page|null */
        return parent::getById($id);
    }

    /**
     * Get all pages (override with specific return type)
     *
     * @param bool $activeOnly
     * @return Page[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        /** @var Page[] */
        return parent::getAll($activeOnly);
    }

    /**
     * Save page (override with specific return type)
     *
     * @param Page $page
     * @return Page
     * @throws \RuntimeException
     */
    public function save($page): Page
    {
        /** @var Page */
        return parent::save($page);
    }

    /**
     * Delete page (override with homepage protection)
     *
     * @param int $pageId
     * @return bool
     * @throws \RuntimeException if trying to delete homepage
     */
    public function delete(int $pageId): bool
    {
        // Check if this is the homepage
        if ($this->isHomepage($pageId)) {
            throw new \RuntimeException(
                'Homepage (page_id=' . Page::HOMEPAGE_ID . ') cannot be deleted. ' .
                'The site requires a homepage to function properly.'
            );
        }

        return parent::delete($pageId);
    }

    /**
     * Get page by URL key
     *
     * @param string $urlKey
     * @return Page|null
     */
    public function getByUrlKey(string $urlKey): ?Page
    {
        $pageData = $this->resource->getByUrlKey($urlKey);

        if (!$pageData) {
            return null;
        }

        return $this->createModel($pageData);
    }

    /**
     * Get homepage
     *
     * @return Page
     * @throws \RuntimeException if homepage doesn't exist
     */
    public function getHomepage(): Page
    {
        $data = $this->resource->getHomepage();

        if (empty($data)) {
            throw new \RuntimeException(
                'Homepage not found! The site requires a homepage (page_id=1, is_homepage=true). ' .
                'Please run the database setup script to create the homepage.'
            );
        }

        return $this->createModel($data);
    }

    /**
     * Check if page is homepage
     *
     * @param int $pageId
     * @return bool
     */
    public function isHomepage(int $pageId): bool
    {
        return $this->resource->isHomepage($pageId);
    }
}
