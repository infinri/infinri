<?php
declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Listing;

use Infinri\Cms\Model\Repository\PageRepository;

/**
 * CMS Page Listing Data Provider
 */
class DataProvider extends AbstractDataProvider
{
    protected function getRepositoryClass(): string
    {
        return PageRepository::class;
    }

    protected function mapEntityToArray($entity): array
    {
        return [
            'page_id' => $entity->getPageId(),
            'title' => $entity->getTitle(),
            'url_key' => $entity->getUrlKey(),
            'is_active' => $entity->getData('is_active'),
            'creation_time' => $entity->getData('created_at'),
            'update_time' => $entity->getData('updated_at'),
        ];
    }
}
