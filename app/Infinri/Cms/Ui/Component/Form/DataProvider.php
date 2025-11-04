<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Form;

use Infinri\Cms\Model\Repository\PageRepository;

/**
 * CMS Page Form Data Provider.
 */
class DataProvider extends AbstractDataProvider
{
    protected function getRepositoryClass(): string
    {
        return PageRepository::class;
    }

    protected function getDefaultData(): array
    {
        return [
            'is_active' => true,
            'content' => '',
        ];
    }

    protected function mapEntityToArray($entity): array
    {
        return [
            'page_id' => $entity->getPageId(),
            'title' => $entity->getTitle(),
            'url_key' => $entity->getUrlKey(),
            'content' => $entity->getData('content'),
            'meta_title' => $entity->getData('meta_title'),
            'meta_description' => $entity->getData('meta_description'),
            'meta_keywords' => $entity->getData('meta_keywords'),
            'is_active' => $entity->getData('is_active'),
        ];
    }
}
