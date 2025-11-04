<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Listing\Column;

/**
 * Block Actions Column
 * Provides Edit and Delete actions for each row.
 */
class BlockActions
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        if (! isset($this->data['name'])) {
            $this->data['name'] = 'actions';
        }
    }

    public function setData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

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
                $name = $this->getData('name');
                if (isset($item['block_id'])) {
                    $item[$name] = [
                        'edit' => [
                            'href' => $this->buildUrl('cms/block/edit', ['id' => $item['block_id']]),
                            'label' => 'Edit',
                        ],
                        'delete' => [
                            'href' => $this->buildUrl('cms/block/delete', ['id' => $item['block_id']]),
                            'label' => 'Delete',
                            'confirm' => [
                                'title' => 'Delete Block',
                                'message' => 'Are you sure you want to delete this block?',
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildUrl(string $path, array $params = []): string
    {
        $query = http_build_query($params);
        $normalizedPath = ltrim($path, '/');
        $base = '/admin/' . $normalizedPath;

        return $query ? $base . '?' . $query : $base;
    }
}
