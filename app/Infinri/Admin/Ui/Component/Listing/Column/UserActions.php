<?php
declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Listing\Column;

/**
 * Admin User Actions Column
 */
class UserActions
{
    /**
     * Prepare Data Source
     */
    public function prepareDataSource(array $dataSource): array
    {
        error_log("UserActions::prepareDataSource called");
        error_log("DataSource items count: " . count($dataSource['data']['items'] ?? []));
        
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['user_id'])) {
                    error_log("Adding actions for user_id: " . $item['user_id']);
                    $item['actions']['edit'] = [
                        'href' => '/admin/user/user/edit?id=' . $item['user_id'],
                        'label' => 'Edit'
                    ];
                    $item['actions']['delete'] = [
                        'href' => '/admin/user/user/delete?id=' . $item['user_id'],
                        'label' => 'Delete',
                        'confirm' => [
                            'title' => 'Delete User',
                            'message' => 'Are you sure you want to delete this user?'
                        ]
                    ];
                }
            }
        }
        
        error_log("Returning dataSource with actions");
        return $dataSource;
    }
}
