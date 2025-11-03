<?php
declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Widget;

/**
 * Service contract for CMS widget CRUD operations
 */
interface WidgetRepositoryInterface
{
    /**
     * Get widget by ID
     *
     * @param int $widgetId
     * @return Widget
     * @throws \RuntimeException if widget not found
     */
    public function getById(int $widgetId): Widget;

    /**
     * Get all widgets for a page (sorted by sort_order)
     *
     * @param int $pageId
     * @param bool $activeOnly Include only active widgets
     * @return Widget[]
     */
    public function getByPageId(int $pageId, bool $activeOnly = true): array;

    /**
     * Save widget
     *
     * @param Widget $widget
     * @return Widget
     * @throws \InvalidArgumentException if validation fails
     */
    public function save(Widget $widget): Widget;

    /**
     * Delete widget
     *
     * @param int $widgetId
     * @return bool
     * @throws \RuntimeException if widget not found
     */
    public function delete(int $widgetId): bool;

    /**
     * Reorder widgets for a page
     *
     * @param int $pageId
     * @param array<int> $widgetIds Array of widget IDs in desired order
     * @return bool
     * @throws \RuntimeException if reorder fails
     */
    public function reorder(int $pageId, array $widgetIds): bool;
}
