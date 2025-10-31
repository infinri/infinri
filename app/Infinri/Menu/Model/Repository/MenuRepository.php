<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\Repository;

use Infinri\Menu\Api\MenuRepositoryInterface;
use Infinri\Menu\Model\Menu;
use Infinri\Menu\Model\ResourceModel\Menu as MenuResource;

/**
 * Menu Repository
 * 
 * Provides CRUD operations for Menu entities
 */
class MenuRepository implements MenuRepositoryInterface
{
    /**
     * Constructor
     *
     * @param MenuResource $resource
     */
    public function __construct(
        private readonly MenuResource $resource
    ) {}

    /**
     * Create a new menu instance
     *
     * @param array $data
     * @return Menu
     */
    public function create(array $data = []): Menu
    {
        return new Menu($this->resource, $data);
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): ?Menu
    {
        $data = $this->resource->getById($id);
        
        if (!$data) {
            return null;
        }
        
        return $this->create($data);
    }

    /**
     * @inheritDoc
     */
    public function getByIdentifier(string $identifier): ?Menu
    {
        $data = $this->resource->getByIdentifier($identifier);
        
        if (!$data) {
            return null;
        }
        
        return $this->create($data);
    }

    /**
     * @inheritDoc
     */
    public function getAll(bool $activeOnly = false): array
    {
        $menuData = $this->resource->getAll($activeOnly);
        
        $menus = [];
        foreach ($menuData as $data) {
            $menus[] = $this->create($data);
        }
        
        return $menus;
    }

    /**
     * @inheritDoc
     */
    public function save(Menu $menu): Menu
    {
        // Validate before saving
        $menu->validate();
        
        $data = $menu->getData();
        
        if ($menu->getMenuId()) {
            // Update existing
            $this->resource->update($menu->getMenuId(), $data);
        } else {
            // Insert new
            $id = $this->resource->insert($data);
            $menu->setMenuId($id);
        }
        
        return $menu;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $menuId): bool
    {
        if (!$this->exists($menuId)) {
            throw new \RuntimeException("Menu with ID {$menuId} does not exist");
        }
        
        return $this->resource->delete($menuId);
    }

    /**
     * @inheritDoc
     */
    public function exists(int $menuId): bool
    {
        return $this->resource->getById($menuId) !== null;
    }
}
