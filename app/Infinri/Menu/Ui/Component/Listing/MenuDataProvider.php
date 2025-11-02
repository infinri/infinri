<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Listing;

use Infinri\Menu\Model\Repository\MenuRepository;

/**
 * Menu Listing Data Provider
 * 
 * Provides data for the menu grid
 */
class MenuDataProvider
{
    /**
     * Constructor
     *
     * @param MenuRepository $menuRepository
     */
    public function __construct(
        private readonly MenuRepository $menuRepository
    ) {}

    /**
     * Get data for grid
     *
     * @return array
     */
    public function getData(): array
    {
        $menus = $this->menuRepository->getAll();
        
        $items = [];
        foreach ($menus as $menu) {
            $items[] = [
                'menu_id' => $menu->getMenuId(),
                'identifier' => $menu->getIdentifier(),
                'title' => $menu->getTitle(),
                'is_active' => $menu->isActive() ? 'Yes' : 'No',
                'created_at' => $menu->getCreatedAt(),
                'updated_at' => $menu->getUpdatedAt(),
            ];
        }
        
        return [
            'items' => $items,
            'totalRecords' => count($items)
        ];
    }
}
