<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Api\BlockRepositoryInterface;
use Infinri\Cms\Model\AbstractContentEntity;
use Infinri\Cms\Model\Block;
use Infinri\Cms\Model\ResourceModel\Block as BlockResource;

/**
 * Provides CRUD operations for CMS blocks.
 */
class BlockRepository extends AbstractContentRepository implements BlockRepositoryInterface
{
    /**
     * Constructor.
     */
    public function __construct(BlockResource $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Create model instance (implements abstract method).
     *
     * @param array<string, mixed> $data
     */
    protected function createModel(array $data = []): Block
    {
        /** @var BlockResource $resource */
        $resource = $this->resource;

        return new Block($resource, $data);
    }

    /**
     * Get entity ID field name (implements abstract method).
     */
    protected function getEntityIdField(): string
    {
        return 'block_id';
    }

    /**
     * Create a new block instance
     * Public factory method for creating empty blocks.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data = []): Block
    {
        return $this->createModel($data);
    }

    /**
     * Get block by ID (override with specific return type).
     */
    public function getById(int $blockId): ?Block
    {
        $result = parent::getById($blockId);
        \assert($result instanceof Block || null === $result);

        return $result;
    }

    /**
     * Get all blocks (override with specific return type).
     *
     * @return array<Block>
     */
    public function getAll(bool $activeOnly = false): array
    {
        $result = parent::getAll($activeOnly);

        /* @var array<Block> $result */
        return $result;
    }

    /**
     * Save block (override with specific return type).
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException If not a Block instance
     */
    public function save(AbstractContentEntity $block): Block
    {
        if (!$block instanceof Block) {
            throw new \InvalidArgumentException('Expected Block instance');
        }
        $saved = parent::save($block);
        \assert($saved instanceof Block);

        return $saved;
    }

    /**
     * Get block by identifier.
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
