<?php

declare(strict_types=1);

namespace Infinri\Menu\Service;

use Infinri\Menu\Model\Repository\MenuItemRepository;

/**
 * Business logic for building hierarchical menu trees with resolved URLs
 */
class MenuBuilder
{
    /**
     * Constructor
     *
     * @param MenuItemRepository $menuItemRepository
     * @param MenuItemResolver $menuItemResolver
     */
    public function __construct(
        private readonly MenuItemRepository $menuItemRepository,
        private readonly MenuItemResolver   $menuItemResolver
    ) {}

    /**
     * Build hierarchical menu tree with resolved URLs
     *
     * @param string $identifier Menu identifier (e.g., 'main-navigation')
     * @param bool $activeOnly Only include active items
     * @return array Nested array of menu items
     */
    public function buildMenu(string $identifier, bool $activeOnly = true): array
    {
        // Get all menu items for this menu (flat array, sorted)
        $menuItems = $this->menuItemRepository->getByMenuIdentifier($identifier, $activeOnly);

        if (empty($menuItems)) {
            return [];
        }

        // Build tree structure (parent/child relationships)
        $tree = $this->buildTree($menuItems);

        // Resolve URLs for each item based on link_type
        return $this->resolveUrls($tree);
    }

    /**
     * Build tree structure from flat array of items
     *
     * @param array $items Flat array of menu items
     * @param int|null $parentId Parent item ID (null for root level)
     * @return array Hierarchical tree structure
     */
    private function buildTree(array $items, ?int $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            // Match parent_item_id with $parentId
            $itemParentId = $item['parent_item_id'] ?? null;

            if ($itemParentId == $parentId) {
                // Found item belonging to this parent
                $itemId = $item['item_id'];

                // Recursively get children
                $children = $this->buildTree($items, $itemId);

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }

    /**
     * Resolve URLs for all menu items recursively
     *
     * @param array $items Menu items tree
     * @return array Menu items with resolved URLs
     */
    private function resolveUrls(array $items): array
    {
        foreach ($items as &$item) {
            // Resolve URL for this item
            $item['url'] = $this->menuItemResolver->resolve($item);

            // Recursively resolve child URLs
            if (!empty($item['children'])) {
                $item['children'] = $this->resolveUrls($item['children']);
            }
        }

        return $items;
    }

    /**
     * Get menu items count for a menu
     *
     * @param string $identifier Menu identifier
     * @param bool $activeOnly Only count active items
     * @return int Number of items
     */
    public function getItemsCount(string $identifier, bool $activeOnly = true): int
    {
        $items = $this->menuItemRepository->getByMenuIdentifier($identifier, $activeOnly);
        return count($items);
    }

    /**
     * Check if menu has items
     *
     * @param string $identifier Menu identifier
     * @param bool $activeOnly Only check active items
     * @return bool True if menu has items
     */
    public function hasItems(string $identifier, bool $activeOnly = true): bool
    {
        return $this->getItemsCount($identifier, $activeOnly) > 0;
    }
}
