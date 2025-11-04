<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\Repository;

use Infinri\Menu\Api\MenuRepositoryInterface;
use Infinri\Menu\Model\Menu;
use Infinri\Menu\Model\ResourceModel\Menu as MenuResource;

/**
 * Provides CRUD operations for Menu entities.
 */
class MenuRepository implements MenuRepositoryInterface
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly MenuResource $resource
    ) {
    }

    /**
     * Create a new menu instance.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data = []): Menu
    {
        return new Menu($this->resource, $data);
    }

    public function getById(int $id): ?Menu
    {
        $data = $this->resource->load($id);

        if (! $data) {
            return null;
        }

        return $this->create($data);
    }

    public function getByIdentifier(string $identifier): ?Menu
    {
        $data = $this->resource->getByIdentifier($identifier);

        if (! $data) {
            return null;
        }

        return $this->create($data);
    }

    public function getAll(bool $activeOnly = false): array
    {
        $menuData = $this->resource->getAll($activeOnly);

        $menus = [];
        foreach ($menuData as $data) {
            $menus[] = $this->create($data);
        }

        return $menus;
    }

    public function save(Menu $menu): Menu
    {
        // Validate before saving
        $menu->validate();

        $data = $menu->getData();

        // Use AbstractResource's save() method which handles both insert and update
        $id = $this->resource->save($data);

        // Set menu ID if it's a new menu
        if (! $menu->getMenuId()) {
            $menu->setMenuId($id);
        }

        return $menu;
    }

    public function delete(int $menuId): bool
    {
        if (! $this->exists($menuId)) {
            throw new \RuntimeException("Menu with ID {$menuId} does not exist");
        }

        return $this->resource->delete($menuId) > 0;
    }

    public function exists(int $menuId): bool
    {
        return false !== $this->resource->load($menuId);
    }
}
