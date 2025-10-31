<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Listing\Column;

/**
 * Menu Item Actions Column
 * 
 * Generates action links for menu item grid rows
 */
class MenuItemActions
{
    /**
     * Prepare data source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['item_id'])) {
                    $item['actions'] = [
                        'edit' => [
                            'href' => '/admin/menu/menuitem/edit?id=' . $item['item_id'] . '&menu_id=' . $item['menu_id'],
                            'label' => 'Edit'
                        ],
                        'delete' => [
                            'href' => '/admin/menu/menuitem/delete?id=' . $item['item_id'] . '&menu_id=' . $item['menu_id'],
                            'label' => 'Delete',
                            'confirm' => [
                                'title' => 'Delete Menu Item',
                                'message' => 'Are you sure you want to delete this menu item?'
                            ]
                        ]
                    ];
                }
            }
        }
        
        return $dataSource;
    }
}
