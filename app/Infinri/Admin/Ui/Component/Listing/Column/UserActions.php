<?php

declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Listing\Column;

/**
 * Admin User Actions Column.
 */
class UserActions
{
    /**
     * Prepare Data Source.
     *
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['user_id'])) {
                    $item['actions']['edit'] = [
                        'href' => '/admin/users/edit?id=' . $item['user_id'],
                        'label' => 'Edit',
                    ];
                    $item['actions']['delete'] = [
                        'href' => '/admin/users/delete?id=' . $item['user_id'],
                        'label' => 'Delete',
                        'confirm' => [
                            'title' => 'Delete User',
                            'message' => 'Are you sure you want to delete this user?',
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
