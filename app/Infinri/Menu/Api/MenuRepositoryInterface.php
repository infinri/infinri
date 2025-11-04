<?php

declare(strict_types=1);

namespace Infinri\Menu\Api;

use Infinri\Menu\Model\Menu;

/**
 * Provides CRUD operations for Menu entities.
 */
interface MenuRepositoryInterface
{
    /**
     * Get menu by ID.
     */
    public function getById(int $id): ?Menu;

    /**
     * Get menu by identifier.
     */
    public function getByIdentifier(string $identifier): ?Menu;

    /**
     * Get all menus.
     *
     * @return Menu[]
     */
    public function getAll(bool $activeOnly = false): array;

    /**
     * Save menu.
     *
     * @throws \RuntimeException
     */
    public function save(Menu $menu): Menu;

    /**
     * Delete menu.
     *
     * @throws \RuntimeException
     */
    public function delete(int $menuId): bool;

    /**
     * Check if menu exists.
     */
    public function exists(int $menuId): bool;
}
