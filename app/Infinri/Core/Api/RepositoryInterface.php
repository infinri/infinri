<?php
declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Repository Interface
 * 
 * Standard interface for data repositories
 */
interface RepositoryInterface
{
    /**
     * Find entity by ID
     *
     * @param int|string $id
     * @return mixed
     */
    public function getById(int|string $id): mixed;

    /**
     * Save entity
     *
     * @param mixed $entity
     * @return mixed
     */
    public function save(mixed $entity): mixed;

    /**
     * Delete entity
     *
     * @param mixed $entity
     * @return bool
     */
    public function delete(mixed $entity): bool;

    /**
     * Get list of entities
     *
     * @param array<string, mixed> $criteria
     * @return array<mixed>
     */
    public function getList(array $criteria = []): array;
}
