<?php

declare(strict_types=1);

namespace Infinri\Menu\ViewModel;

use Infinri\Menu\Service\MenuBuilder;
use Infinri\Core\App\Request;

/**
 * Provides presentation logic for navigation menus
 */
class Navigation
{
    /**
     * Constructor
     *
     * @param MenuBuilder $menuBuilder
     * @param Request $request
     */
    public function __construct(
        private readonly MenuBuilder $menuBuilder,
        private readonly Request     $request
    ) {}

    /**
     * Get main navigation menu with active states
     *
     * @return array Menu items with active flags
     */
    public function getMainNavigation(): array
    {
        $menuItems = $this->menuBuilder->buildMenu('main-navigation');
        return $this->setActiveStates($menuItems);
    }

    /**
     * Get footer navigation menu
     *
     * @return array Menu items
     */
    public function getFooterNavigation(): array
    {
        return $this->menuBuilder->buildMenu('footer-links');
    }

    /**
     * Get mobile navigation menu
     *
     * @return array Menu items
     */
    public function getMobileNavigation(): array
    {
        // Can use same as main or different menu
        return $this->getMainNavigation();
    }

    /**
     * Get menu by identifier
     *
     * @param string $identifier Menu identifier
     * @return array Menu items with active states
     */
    public function getMenu(string $identifier): array
    {
        $menuItems = $this->menuBuilder->buildMenu($identifier);
        return $this->setActiveStates($menuItems);
    }

    /**
     * Check if menu has items
     *
     * @param string $identifier Menu identifier
     * @return bool
     */
    public function hasItems(string $identifier): bool
    {
        return $this->menuBuilder->hasItems($identifier);
    }

    /**
     * Set active states based on current URL
     *
     * @param array $items Menu items
     * @return array Menu items with active flags
     */
    private function setActiveStates(array $items): array
    {
        $currentPath = $this->request->getPath();

        foreach ($items as &$item) {
            // Check if current URL starts with this item's URL
            $item['active'] = $this->isActive($item['url'], $currentPath);

            // Recursively set active states for children
            if (!empty($item['children'])) {
                $item['children'] = $this->setActiveStates($item['children']);

                // If any child is active, parent should be active too
                if (!$item['active']) {
                    $item['active'] = $this->hasActiveChild($item['children']);
                }
            }
        }

        return $items;
    }

    /**
     * Check if URL is active
     *
     * @param string $itemUrl Menu item URL
     * @param string $currentPath Current request path
     * @return bool
     */
    private function isActive(string $itemUrl, string $currentPath): bool
    {
        // Exact match for homepage
        if ($itemUrl === '/' && $currentPath === '/') {
            return true;
        }

        // For other pages, check if current path starts with item URL
        if ($itemUrl !== '/' && str_starts_with($currentPath, $itemUrl)) {
            return true;
        }

        return false;
    }

    /**
     * Check if any child is active
     *
     * @param array $children Child items
     * @return bool
     */
    private function hasActiveChild(array $children): bool
    {
        foreach ($children as $child) {
            if ($child['active']) {
                return true;
            }

            if (!empty($child['children']) && $this->hasActiveChild($child['children'])) {
                return true;
            }
        }

        return false;
    }
}
