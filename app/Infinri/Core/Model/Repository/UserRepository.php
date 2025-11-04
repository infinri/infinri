<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Repository;

use Infinri\Core\Api\RepositoryInterface;
use Infinri\Core\Model\ResourceModel\User as UserResource;
use Infinri\Core\Model\User;

/**
 * Repository pattern implementation for User entities.
 */
class UserRepository implements RepositoryInterface
{
    public function __construct(
        private readonly UserResource $resource
    ) {
    }

    public function getById(int|string $id): ?User
    {
        $user = new User($this->resource);
        $user->load($id);

        return $user->getId() ? $user : null;
    }

    public function save(mixed $entity): User
    {
        if (! $entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }

        $entity->save();

        return $entity;
    }

    public function delete(mixed $entity): bool
    {
        if (! $entity instanceof User) {
            throw new \InvalidArgumentException('Entity must be instance of User');
        }

        $entity->delete();

        return true;
    }

    public function getList(array $criteria = []): array
    {
        $data = $this->resource->findBy($criteria);
        $users = [];

        foreach ($data as $row) {
            $user = new User($this->resource);
            $user->setData($row);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Find user by email.
     */
    public function getByEmail(string $email): ?User
    {
        $data = $this->resource->findByEmail($email);

        if (false === $data) {
            return null;
        }

        $user = new User($this->resource);
        $user->setData($data);

        return $user;
    }
}
