<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Form;

use Infinri\Cms\Model\Repository\BlockRepository;

/**
 * CMS Block Form Data Provider
 */
class BlockDataProvider extends AbstractDataProvider
{
    protected function getRepositoryClass(): string
    {
        return BlockRepository::class;
    }

    protected function getDefaultData(): array
    {
        return [
            'block_id' => null,
            'title' => '',
            'identifier' => '',
            'content' => '',
            'is_active' => true,
        ];
    }

    protected function mapEntityToArray($entity): array
    {
        return [
            'block_id' => $entity->getBlockId(),
            'title' => $entity->getTitle(),
            'identifier' => $entity->getIdentifier(),
            'content' => $entity->getContent(),
            'is_active' => $entity->isActive(),
        ];
    }
}
