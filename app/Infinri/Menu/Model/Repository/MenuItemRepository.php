<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\Repository;

use Infinri\Menu\Api\MenuItemRepositoryInterface;
use Infinri\Menu\Model\MenuItem;
use Infinri\Menu\Model\ResourceModel\MenuItem as MenuItemResource;

/**
 * Provides CRUD operations for MenuItem entities
 */
class MenuItemRepository implements MenuItemRepositoryInterface
{
    /**
     * Constructor
     *
     * @param MenuItemResource $resource
     */
    public function __construct(
        private readonly MenuItemResource $resource
    ) {}

    /**
     * Create a new menu item instance
     *
     * @param array<string, mixed> $data
     * @return MenuItem
     */
    public function create(array $data = []): MenuItem
    {
        return new MenuItem($this->resource, $data);
    }

    /**
     * @inheritDoc
     */
    public function getById(int $id): ?MenuItem
    {
        $data = $this->resource->load($id);

        if (!$data) {
            return null;
        }

        return $this->create($data);
    }

    public function getByMenuId(int $menuId, bool $activeOnly = false): array
    {
        $itemsData = $this->resource->getByMenuId($menuId, $activeOnly);

        $items = [];
        foreach ($itemsData as $data) {
            $items[] = $this->create($data);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getByMenuIdentifier(string $identifier, bool $activeOnly = true): array
    {
        // Return raw array data for performance (used by MenuBuilder)
        return $this->resource->getByMenuIdentifier($identifier, $activeOnly);
    }

    /**
     * @inheritDoc
     */
    public function getChildren(int $parentItemId, bool $activeOnly = false): array
    {
        $itemsData = $this->resource->getChildren($parentItemId, $activeOnly);

        $items = [];
        foreach ($itemsData as $data) {
            $items[] = $this->create($data);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function save(MenuItem $menuItem): MenuItem
    {
        // Validate before saving
        $menuItem->validate();

        $data = $menuItem->getData();

        // Use AbstractResource's save() method which handles both insert and update
        $id = $this->resource->save($data);

        // Set item ID if it's a new item
        if (!$menuItem->getItemId()) {
            $menuItem->setItemId($id);
        }

        return $menuItem;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $itemId): bool
    {
        $item = $this->getById($itemId);

        if (!$item) {
            throw new \RuntimeException("Menu item with ID {$itemId} does not exist");
        }

        return $this->resource->delete($itemId) > 0;
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $orderData
     * @throws \Exception
     */
    public function reorder(array $orderData): bool
    {
        return $this->resource->reorder($orderData);
    }
}
