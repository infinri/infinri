<?php

declare(strict_types=1);

namespace Infinri\Menu\Api;

use Infinri\Menu\Model\MenuItem;

/**
 * Provides CRUD operations for MenuItem entities
 */
interface MenuItemRepositoryInterface
{
    /**
     * Get menu item by ID
     *
     * @param int $id
     * @return MenuItem|null
     */
    public function getById(int $id): ?MenuItem;

    /**
     * Get all menu items for a specific menu
     *
     * @param int $menuId
     * @param bool $activeOnly
     * @return MenuItem[]
     */
    public function getByMenuId(int $menuId, bool $activeOnly = false): array;

    /**
     * Get menu items by menu identifier
     *
     * @param string $identifier
     * @param bool $activeOnly
     * @return array Raw array data for performance
     */
    public function getByMenuIdentifier(string $identifier, bool $activeOnly = true): array;

    /**
     * Get child items for a parent
     *
     * @param int $parentItemId
     * @param bool $activeOnly
     * @return MenuItem[]
     */
    public function getChildren(int $parentItemId, bool $activeOnly = false): array;

    /**
     * Save menu item
     *
     * @param MenuItem $menuItem
     * @return MenuItem
     * @throws \RuntimeException
     */
    public function save(MenuItem $menuItem): MenuItem;

    /**
     * Delete menu item
     *
     * @param int $itemId
     * @return bool
     * @throws \RuntimeException
     */
    public function delete(int $itemId): bool;

    /**
     * Reorder menu items
     *
     * @param array<string, mixed> $orderData Array of [item_id => sort_order]
     * @return bool
     */
    public function reorder(array $orderData): bool;
}
