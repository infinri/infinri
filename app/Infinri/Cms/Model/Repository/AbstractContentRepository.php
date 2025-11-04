<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\Repository;

use Infinri\Cms\Model\AbstractContentEntity;
use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Base repository for all CMS content entities
 * Provides common CRUD operations with consistent interface.
 */
abstract class AbstractContentRepository
{
    /**
     * Constructor.
     */
    public function __construct(
        protected readonly AbstractResource $resource
    ) {
    }

    /**
     * Create model instance with data
     * Each repository must implement its specific model creation.
     *
     * @param array<string, mixed> $data
     */
    abstract protected function createModel(array $data = []): AbstractContentEntity;

    /**
     * Get entity ID field name (e.g., 'page_id', 'block_id').
     */
    abstract protected function getEntityIdField(): string;

    /**
     * Get entity by ID.
     */
    public function getById(int $id): ?AbstractContentEntity
    {
        $data = $this->resource->load($id);

        if (empty($data)) {
            return null;
        }

        return $this->createModel($data);
    }

    /**
     * Get all entities.
     *
     * @param bool $activeOnly Filter to only active entities
     *
     * @return AbstractContentEntity[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        $dataArray = $this->resource->getAll($activeOnly);
        $entities = [];

        foreach ($dataArray as $data) {
            $entities[] = $this->createModel($data);
        }

        return $entities;
    }

    /**
     * Save entity
     * Validates, persists to database, and returns reloaded entity.
     *
     * @throws \RuntimeException
     */
    public function save(AbstractContentEntity $entity): AbstractContentEntity
    {
        // Validate before save
        $entity->validate();

        // Save to database - returns entity ID
        $entityId = $this->resource->save($entity->getData());

        // Reload from database to get all updated fields
        $reloaded = $this->getById($entityId);

        if (! $reloaded) {
            throw new \RuntimeException('Failed to reload ' . $entity::class . ' after save');
        }

        return $reloaded;
    }

    /**
     * Delete entity by ID.
     *
     * @return bool True if deleted, false if not found
     */
    public function delete(int $id): bool
    {
        $entity = $this->getById($id);

        if (! $entity) {
            return false;
        }

        return $this->resource->delete($id) > 0;
    }

    /**
     * Check if entity with ID exists.
     */
    public function exists(int $id): bool
    {
        return null !== $this->getById($id);
    }

    /**
     * Get total count of entities
     * Uses efficient database count query instead of loading all records.
     *
     * @param bool $activeOnly Count only active entities
     */
    public function count(bool $activeOnly = false): int
    {
        // Cast to AbstractContentResource to use the correct count method
        /** @var \Infinri\Cms\Model\ResourceModel\AbstractContentResource $resource */
        $resource = $this->resource;

        return $resource->count($activeOnly);
    }
}
