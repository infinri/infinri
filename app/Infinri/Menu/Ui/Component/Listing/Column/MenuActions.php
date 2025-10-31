<?php

declare(strict_types=1);

namespace Infinri\Menu\Ui\Component\Listing\Column;

/**
 * Menu Actions Column
 * 
 * Generates action links for menu grid rows
 */
class MenuActions
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
                if (isset($item['menu_id'])) {
                    $item['actions'] = [
                        'edit' => [
                            'href' => '/admin/menu/menu/edit?id=' . $item['menu_id'],
                            'label' => 'Edit'
                        ],
                        'items' => [
                            'href' => '/admin/menu/menuitem/index?menu_id=' . $item['menu_id'],
                            'label' => 'Manage Items'
                        ],
                        'delete' => [
                            'href' => '/admin/menu/menu/delete?id=' . $item['menu_id'],
                            'label' => 'Delete',
                            'confirm' => [
                                'title' => 'Delete Menu',
                                'message' => 'Are you sure you want to delete this menu? All menu items will also be deleted.'
                            ]
                        ]
                    ];
                }
            }
        }
        
        return $dataSource;
    }
}
