<?php

declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Page;

/**
 * Provides CRUD operations for CMS pages with homepage protection.
 */
interface PageRepositoryInterface
{
    /**
     * Get page by ID.
     */
    public function getById(int $pageId): ?Page;

    /**
     * Get page by URL key.
     */
    public function getByUrlKey(string $urlKey): ?Page;

    /**
     * Get homepage.
     *
     * @throws \RuntimeException if homepage doesn't exist
     */
    public function getHomepage(): Page;

    /**
     * Get all pages.
     *
     * @return Page[]
     */
    public function getAll(bool $activeOnly = false): array;

    /**
     * Save page.
     */
    public function save(Page $page): Page;

    /**
     * Delete page.
     *
     * @throws \RuntimeException if trying to delete homepage
     */
    public function delete(int $pageId): bool;

    /**
     * Check if page is homepage.
     */
    public function isHomepage(int $pageId): bool;
}
