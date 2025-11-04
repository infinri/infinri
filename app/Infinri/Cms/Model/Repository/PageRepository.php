<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Api\PageRepositoryInterface;
use Infinri\Cms\Model\AbstractContentEntity;
use Infinri\Cms\Model\Page;
use Infinri\Cms\Model\ResourceModel\Page as PageResource;

/**
 * Provides CRUD operations for CMS pages with homepage protection.
 */
class PageRepository extends AbstractContentRepository implements PageRepositoryInterface
{
    /**
     * Constructor.
     */
    public function __construct(PageResource $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Create model instance (implements abstract method).
     *
     * @param array<string, mixed> $data
     */
    protected function createModel(array $data = []): Page
    {
        /** @var PageResource $resource */
        $resource = $this->resource;

        return new Page($resource, $data);
    }

    /**
     * Get entity ID field name (implements abstract method).
     */
    protected function getEntityIdField(): string
    {
        return 'page_id';
    }

    /**
     * Create a new page instance
     * Public factory method for creating empty pages.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data = []): Page
    {
        return $this->createModel($data);
    }

    /**
     * Get page by ID (override with specific return type).
     */
    public function getById(int $pageId): ?Page
    {
        $result = parent::getById($pageId);
        \assert($result instanceof Page || null === $result);

        return $result;
    }

    /**
     * Get all pages (override with specific return type).
     *
     * @return array<Page>
     */
    public function getAll(bool $activeOnly = false): array
    {
        $result = parent::getAll($activeOnly);
        /** @var array<Page> $result */
        return $result;
    }

    /**
     * Save page.
     *
     * @param AbstractContentEntity $page Must be a Page instance
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException If not a Page instance
     */
    public function save(AbstractContentEntity $page): Page
    {
        if (! $page instanceof Page) {
            throw new \InvalidArgumentException('Expected Page instance');
        }
        $saved = parent::save($page);
        \assert($saved instanceof Page);

        return $saved;
    }

    /**
     * Delete page (override with homepage protection).
     *
     * @throws \RuntimeException if trying to delete homepage
     */
    public function delete(int $pageId): bool
    {
        // Check if this is the homepage
        if ($this->isHomepage($pageId)) {
            throw new \RuntimeException('Homepage (page_id=' . Page::HOMEPAGE_ID . ') cannot be deleted. The site requires a homepage to function properly.');
        }

        return parent::delete($pageId);
    }

    /**
     * Get page by URL key.
     */
    public function getByUrlKey(string $urlKey): ?Page
    {
        $pageData = $this->resource->getByUrlKey($urlKey);

        if (! $pageData) {
            return null;
        }

        return $this->createModel($pageData);
    }

    /**
     * Get homepage.
     *
     * @throws \RuntimeException if homepage doesn't exist
     */
    public function getHomepage(): Page
    {
        $data = $this->resource->getHomepage();

        if (empty($data)) {
            throw new \RuntimeException('Homepage not found! The site requires a homepage (page_id=1, is_homepage=true). Please run the database setup script to create the homepage.');
        }

        return $this->createModel($data);
    }

    /**
     * Check if page is homepage.
     */
    public function isHomepage(int $pageId): bool
    {
        return $this->resource->isHomepage($pageId);
    }
}
