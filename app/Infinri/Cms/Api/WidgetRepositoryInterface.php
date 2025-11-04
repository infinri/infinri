<?php

declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Widget;

/**
 * Service contract for CMS widget CRUD operations.
 */
interface WidgetRepositoryInterface
{
    /**
     * Get widget by ID.
     *
     * @throws \RuntimeException if widget not found
     */
    public function getById(int $widgetId): Widget;

    /**
     * Get all widgets for a page (sorted by sort_order).
     *
     * @param bool $activeOnly Include only active widgets
     *
     * @return Widget[]
     */
    public function getByPageId(int $pageId, bool $activeOnly = true): array;

    /**
     * Save widget.
     *
     * @throws \InvalidArgumentException if validation fails
     */
    public function save(Widget $widget): Widget;

    /**
     * Delete widget.
     *
     * @throws \RuntimeException if widget not found
     */
    public function delete(int $widgetId): bool;

    /**
     * Reorder widgets for a page.
     *
     * @param array<int> $widgetIds Array of widget IDs in desired order
     *
     * @throws \RuntimeException if reorder fails
     */
    public function reorder(int $pageId, array $widgetIds): bool;
}
