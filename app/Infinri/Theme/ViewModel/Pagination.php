<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

/**
 * Manages pagination for list pages.
 */
class Pagination
{
    /**
     * Current page number.
     */
    private int $currentPage = 1;

    /**
     * Total number of pages.
     */
    private int $totalPages = 1;

    /**
     * Items per page.
     */
    private int $pageSize = 20;

    /**
     * Total items count.
     */
    private int $totalItems = 0;

    /**
     * Base URL for pagination links.
     */
    private string $baseUrl = '';

    /**
     * Set pagination parameters.
     *
     * @param int $currentPage Current page
     * @param int $totalItems  Total items
     * @param int $pageSize    Items per page
     */
    public function setPagination(int $currentPage, int $totalItems, int $pageSize = 20): void
    {
        $this->currentPage = max(1, $currentPage);
        $this->totalItems = max(0, $totalItems);
        $this->pageSize = max(1, $pageSize);
        $this->totalPages = (int) ceil($this->totalItems / $this->pageSize);
    }

    /**
     * Set base URL for pagination.
     *
     * @param string $baseUrl Base URL
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get current page number.
     *
     * @return int Current page
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get total pages.
     *
     * @return int Total pages
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Get page size.
     *
     * @return int Items per page
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Get total items.
     *
     * @return int Total items
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Get URL for specific page.
     *
     * @param int $page Page number
     *
     * @return string Page URL
     */
    public function getPageUrl(int $page): string
    {
        return $this->baseUrl . '?page=' . $page;
    }

    /**
     * Check if has previous page.
     *
     * @return bool True if has previous
     */
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if has next page.
     *
     * @return bool True if has next
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page URL.
     *
     * @return string|null Previous page URL or null
     */
    public function getPreviousUrl(): ?string
    {
        return $this->hasPrevious() ? $this->getPageUrl($this->currentPage - 1) : null;
    }

    /**
     * Get next page URL.
     *
     * @return string|null Next page URL or null
     */
    public function getNextUrl(): ?string
    {
        return $this->hasNext() ? $this->getPageUrl($this->currentPage + 1) : null;
    }

    /**
     * Get page numbers to display.
     *
     * @param int $delta Number of pages before/after current
     *
     * @return array Page numbers
     */
    public function getPages(int $delta = 2): array
    {
        $pages = [];
        $start = max(1, $this->currentPage - $delta);
        $end = min($this->totalPages, $this->currentPage + $delta);

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        return $pages;
    }

    /**
     * Check if should show first page link.
     *
     * @param int $delta Delta for page range
     *
     * @return bool True if should show
     */
    public function shouldShowFirst(int $delta = 2): bool
    {
        return $this->currentPage - $delta > 1;
    }

    /**
     * Check if should show last page link.
     *
     * @param int $delta Delta for page range
     *
     * @return bool True if should show
     */
    public function shouldShowLast(int $delta = 2): bool
    {
        return $this->currentPage + $delta < $this->totalPages;
    }
}
