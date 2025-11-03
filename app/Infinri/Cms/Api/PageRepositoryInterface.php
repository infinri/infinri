<?php

declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Page;

/**
 * Provides CRUD operations for CMS pages with homepage protection
 */
interface PageRepositoryInterface
{
    /**
     * Get page by ID
     *
     * @param int $pageId
     * @return Page|null
     */
    public function getById(int $pageId): ?Page;

    /**
     * Get page by URL key
     *
     * @param string $urlKey
     * @return Page|null
     */
    public function getByUrlKey(string $urlKey): ?Page;

    /**
     * Get homepage
     *
     * @return Page
     * @throws \RuntimeException if homepage doesn't exist
     */
    public function getHomepage(): Page;

    /**
     * Get all pages
     *
     * @param bool $activeOnly
     * @return Page[]
     */
    public function getAll(bool $activeOnly = false): array;

    /**
     * Save page
     *
     * @param Page $page
     * @return Page
     */
    public function save(Page $page): Page;

    /**
     * Delete page
     *
     * @param int $pageId
     * @return bool
     * @throws \RuntimeException if trying to delete homepage
     */
    public function delete(int $pageId): bool;

    /**
     * Check if page is homepage
     *
     * @param int $pageId
     * @return bool
     */
    public function isHomepage(int $pageId): bool;
}
