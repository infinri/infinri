<?php

declare(strict_types=1);

namespace Infinri\Admin\Block;

use Infinri\Admin\Model\Menu\MenuLoader;
use Infinri\Core\Block\Template;

/**
 * Renders the admin navigation sidebar.
 */
class Menu extends Template
{
    public function __construct(
        private readonly MenuLoader $menuLoader
    ) {
    }

    /**
     * Get menu items loaded from menu.xml files.
     *
     * @return array<string, mixed>
     */
    public function getMenuItems(): array
    {
        $items = $this->menuLoader->getMenuItems();

        // Process all items recursively (add URL and active state)
        return $this->processMenuItems($items);
    }

    /**
     * Recursively process menu items to add URLs and active states.
     *
     * @param array<string, mixed> $items
     *
     * @return array<string, mixed>
     */
    private function processMenuItems(array $items): array
    {
        foreach ($items as &$item) {
            if (isset($item['action']) && '' !== $item['action']) {
                $item['url'] = '/' . ltrim($item['action'], '/');
            }
            $item['active'] = $this->isActive($item);

            // Recursively process children at any level
            if (isset($item['children']) && \is_array($item['children']) && \count($item['children']) > 0) {
                $item['children'] = $this->processMenuItems($item['children']);
            }
        }

        return $items;
    }

    /**
     * Check if menu item is active.
     *
     * @param array<string, mixed> $item
     */
    public function isActive(array $item): bool
    {
        return $item['active'] ?? false;
    }

    /**
     * Check if menu item has children.
     *
     * @param array<string, mixed> $item
     */
    public function hasChildren(array $item): bool
    {
        return isset($item['children']) && \is_array($item['children']) && \count($item['children']) > 0;
    }

    /**
     * Get default template.
     */
    public function getTemplate(): string
    {
        return null !== $this->template ? $this->template : 'Infinri_Theme::html/menu.phtml';
    }
}
