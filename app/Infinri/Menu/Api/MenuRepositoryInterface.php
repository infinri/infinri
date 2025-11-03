<?php

declare(strict_types=1);

namespace Infinri\Menu\Api;

use Infinri\Menu\Model\Menu;

/**
 * Provides CRUD operations for Menu entities
 */
interface MenuRepositoryInterface
{
    /**
     * Get menu by ID
     *
     * @param int $id
     * @return Menu|null
     */
    public function getById(int $id): ?Menu;

    /**
     * Get menu by identifier
     *
     * @param string $identifier
     * @return Menu|null
     */
    public function getByIdentifier(string $identifier): ?Menu;

    /**
     * Get all menus
     *
     * @param bool $activeOnly
     * @return Menu[]
     */
    public function getAll(bool $activeOnly = false): array;

    /**
     * Save menu
     *
     * @param Menu $menu
     * @return Menu
     * @throws \RuntimeException
     */
    public function save(Menu $menu): Menu;

    /**
     * Delete menu
     *
     * @param int $menuId
     * @return bool
     * @throws \RuntimeException
     */
    public function delete(int $menuId): bool;

    /**
     * Check if menu exists
     *
     * @param int $menuId
     * @return bool
     */
    public function exists(int $menuId): bool;
}
