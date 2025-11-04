<?php

declare(strict_types=1);

namespace Infinri\Admin\Model\Menu;

use Infinri\Core\Helper\Logger;

/**
 * Loads and merges menu.xml files from all modules.
 */
class MenuLoader
{
    private const MENU_XML_PATH = 'etc/adminhtml/menu.xml';

    private array $menuItems = [];

    private bool $loaded = false;

    /**
     * Get all menu items.
     */
    public function getMenuItems(): array
    {
        if (! $this->loaded) {
            $this->loadMenuXml();
            $this->buildMenuTree();
            $this->loaded = true;
        }

        return $this->menuItems;
    }

    /**
     * Load menu.xml files from all modules.
     */
    private function loadMenuXml(): void
    {
        $appDir = \dirname(__DIR__, 3);

        if (! is_dir($appDir)) {
            Logger::warning('MenuLoader: appDir not found', ['path' => $appDir]);

            return;
        }
        $modules = scandir($appDir);
        $rawItems = [];

        foreach ($modules as $module) {
            if ('.' === $module || '..' === $module) {
                continue;
            }

            $menuFile = $appDir . '/' . $module . '/' . self::MENU_XML_PATH;

            if (! file_exists($menuFile)) {
                continue;
            }

            $xml = simplexml_load_file($menuFile);
            if (false === $xml) {
                Logger::warning('MenuLoader: Failed to parse XML', ['file' => $menuFile]);
                continue;
            }

            // Parse menu items from this module
            foreach ($xml->menu->add as $item) {
                $id = (string) $item['id'];
                $rawItems[$id] = [
                    'id' => $id,
                    'title' => (string) $item['title'],
                    'module' => (string) $item['module'],
                    'sortOrder' => (int) ($item['sortOrder'] ?? 100),
                    'parent' => (string) ($item['parent'] ?? ''),
                    'action' => (string) ($item['action'] ?? ''),
                    'resource' => (string) ($item['resource'] ?? ''),
                    'icon' => (string) ($item['icon'] ?? ''),
                ];
            }
        }

        // Sort by sortOrder
        uasort($rawItems, function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });

        $this->menuItems = $rawItems;
    }

    /**
     * Build hierarchical menu tree (supports unlimited nesting).
     */
    private function buildMenuTree(): void
    {
        $items = $this->menuItems;

        // Initialize children array for all items
        foreach ($items as $id => $item) {
            $items[$id]['children'] = [];
        }

        // Build tree recursively by attaching children to parents
        $tree = [];
        foreach ($items as $id => $item) {
            if (empty($item['parent'])) {
                // Top-level item
                $tree[$id] = $item;
            } elseif (isset($items[$item['parent']])) {
                // Child item - attach to parent
                $items[$item['parent']]['children'][$id] = $item;
            }
        }

        // Now recursively attach grandchildren and beyond
        $tree = $this->attachNestedChildren($tree, $items);

        $this->menuItems = $tree;
    }

    /**
     * Recursively attach nested children to their parents.
     *
     * @param array<string, mixed> $tree
     * @param array<string, mixed> $allItems
     *
     * @return array<string, mixed>
     */
    private function attachNestedChildren(array $tree, array $allItems): array
    {
        foreach ($tree as $id => &$item) {
            if (isset($allItems[$id]['children']) && ! empty($allItems[$id]['children'])) {
                $item['children'] = $allItems[$id]['children'];
                // Recursively process children
                $item['children'] = $this->attachNestedChildren($item['children'], $allItems);
            }
        }

        return $tree;
    }
}
