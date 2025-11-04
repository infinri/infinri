<?php

declare(strict_types=1);

namespace Infinri\Seo\Ui\Component\Listing\Column;

/**
 * Redirect Actions Column.
 */
class RedirectActions
{
    /**
     * Prepare data source for actions column.
     *
     * @param array<string, mixed> $dataSource
     *
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['redirect_id'])) {
                    $item['actions'] = [
                        'edit' => [
                            'href' => '/admin/seo/redirect/edit?id=' . $item['redirect_id'],
                            'label' => 'Edit',
                        ],
                        'delete' => [
                            'href' => '/admin/seo/redirect/delete?id=' . $item['redirect_id'],
                            'label' => 'Delete',
                            'confirm' => [
                                'title' => 'Delete Redirect',
                                'message' => 'Are you sure you want to delete this redirect?',
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
