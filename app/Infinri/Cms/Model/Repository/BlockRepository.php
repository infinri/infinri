<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Api\BlockRepositoryInterface;
use Infinri\Cms\Model\Block;
use Infinri\Cms\Model\ResourceModel\Block as BlockResource;

/**
 * Provides CRUD operations for CMS blocks.
 */
class BlockRepository extends AbstractContentRepository implements BlockRepositoryInterface
{
    /**
     * Constructor
     *
     * @param BlockResource $resource
     */
    public function __construct(BlockResource $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Create model instance (implements abstract method)
     *
     * @param array $data
     * @return Block
     */
    protected function createModel(array $data = []): Block
    {
        return new Block($this->resource, $data);
    }

    /**
     * Get entity ID field name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityIdField(): string
    {
        return 'block_id';
    }

    /**
     * Create a new block instance
     * Public factory method for creating empty blocks
     *
     * @param array $data
     * @return Block
     */
    public function create(array $data = []): Block
    {
        return $this->createModel($data);
    }

    /**
     * Get block by ID (override with specific return type)
     *
     * @param int $blockId
     * @return Block|null
     */
    public function getById(int $blockId): ?Block
    {
        /** @var Block|null */
        return parent::getById($blockId);
    }

    /**
     * Get all blocks (override with specific return type)
     *
     * @param bool $activeOnly
     * @return Block[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        /** @var Block[] */
        return parent::getAll($activeOnly);
    }

    /**
     * Save block (override with specific return type)
     *
     * @param Block $block
     * @return Block
     * @throws \RuntimeException
     */
    public function save($block): Block
    {
        /** @var Block */
        return parent::save($block);
    }

    /**
     * Get block by identifier
     *
     * @param string $identifier
     * @return Block|null
     */
    public function getByIdentifier(string $identifier): ?Block
    {
        $data = $this->resource->getByIdentifier($identifier);

        if (empty($data)) {
            return null;
        }

        return $this->createModel($data);
    }
}
