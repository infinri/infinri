<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

/**
 * Manages breadcrumb trail for navigation
 */
class Breadcrumb
{
    /**
     * Breadcrumb items
     *
     * @var array
     */
    private array $breadcrumbs = [];

    /**
     * Add a breadcrumb item
     *
     * @param string $label Breadcrumb label
     * @param string|null $url Breadcrumb URL (null for current page)
     * @return void
     */
    public function addCrumb(string $label, ?string $url = null): void
    {
        $this->breadcrumbs[] = [
            'label' => $label,
            'url' => $url,
        ];
    }

    /**
     * Get all breadcrumbs
     *
     * @return array Breadcrumb items
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * Check if breadcrumbs exist
     *
     * @return bool True if has breadcrumbs
     */
    public function hasBreadcrumbs(): bool
    {
        return !empty($this->breadcrumbs);
    }

    /**
     * Get breadcrumb count
     *
     * @return int Number of breadcrumbs
     */
    public function getCount(): int
    {
        return count($this->breadcrumbs);
    }

    /**
     * Clear all breadcrumbs
     *
     * @return void
     */
    public function clear(): void
    {
        $this->breadcrumbs = [];
    }

    /**
     * Get JSON-LD structured data for breadcrumbs
     *
     * @param string $baseUrl Base URL for absolute paths
     * @return string JSON-LD schema
     */
    public function getStructuredData(string $baseUrl = ''): string
    {
        if (empty($this->breadcrumbs)) {
            return '';
        }

        $items = [];
        $position = 1;

        foreach ($this->breadcrumbs as $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['label'],
                'item' => $crumb['url'] ? $baseUrl . $crumb['url'] : null,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
