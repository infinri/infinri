<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Listing;

use Infinri\Cms\Model\Repository\BlockRepository;

/**
 * CMS Block Listing Data Provider.
 */
class BlockDataProvider extends AbstractDataProvider
{
    protected function getRepositoryClass(): string
    {
        return BlockRepository::class;
    }

    protected function mapEntityToArray($entity): array
    {
        return [
            'block_id' => $entity->getBlockId(),
            'identifier' => $entity->getIdentifier(),
            'title' => $entity->getTitle(),
            'is_active' => $entity->isActive(),
            'created_at' => $entity->getCreatedAt(),
            'updated_at' => $entity->getUpdatedAt(),
        ];
    }
}
