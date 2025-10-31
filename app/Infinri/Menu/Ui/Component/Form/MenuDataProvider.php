<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Form;

use Infinri\Menu\Model\Repository\MenuRepository;

/**
 * Menu Form Data Provider
 * 
 * Provides data for menu edit/create form
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
     * Get data for form
     *
     * @param int|null $menuId
     * @return array
     */
    public function getData(?int $menuId = null): array
    {
        if ($menuId === null) {
            return [
                'menu_id' => null,
                'identifier' => '',
                'title' => '',
                'is_active' => true
            ];
        }
        
        $menu = $this->menuRepository->getById($menuId);
        
        if (!$menu) {
            return [];
        }
        
        return [
            'menu_id' => $menu->getMenuId(),
            'identifier' => $menu->getIdentifier(),
            'title' => $menu->getTitle(),
            'is_active' => $menu->isActive()
        ];
    }
}
