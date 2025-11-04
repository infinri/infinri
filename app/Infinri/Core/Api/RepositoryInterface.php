<?php

declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Standard interface for data repositories.
 */
interface RepositoryInterface
{
    /**
     * Find entity by ID.
     */
    public function getById(int|string $id): mixed;

    /**
     * Save entity.
     */
    public function save(mixed $entity): mixed;

    /**
     * Delete entity.
     */
    public function delete(mixed $entity): bool;

    /**
     * Get list of entities.
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<mixed>
     */
    public function getList(array $criteria = []): array;
}
