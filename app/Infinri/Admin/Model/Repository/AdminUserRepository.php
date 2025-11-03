<?php
declare(strict_types=1);

namespace Infinri\Admin\Model\Repository;

use Infinri\Admin\Model\AdminUser;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;

/**
 * Provides CRUD operations for admin users
 */
class AdminUserRepository
{
    public function __construct(
        private readonly AdminUserResource $resource
    ) {}

    /**
     * Get user by ID
     *
     * @param int $id
     * @return AdminUser|null
     */
    public function getById(int $id): ?AdminUser
    {
        $data = $this->resource->load($id);
        
        if (!$data) {
            return null;
        }
        
        return $this->createModel($data);
    }

    /**
     * Get all users
     *
     * @return AdminUser[]
     */
    public function getAll(): array
    {
        $usersData = $this->resource->findAll();
        $users = [];
        
        foreach ($usersData as $userData) {
            $users[] = $this->createModel($userData);
        }
        
        return $users;
    }

    /**
     * Save user
     *
     * @param AdminUser $user
     * @return AdminUser
     */
    public function save(AdminUser $user): AdminUser
    {
        $user->save();
        return $user;
    }

    /**
     * Delete user
     *
     * @param AdminUser $user
     * @return AdminUser
     */
    public function delete(AdminUser $user): AdminUser
    {
        return $user->delete();
    }

    /**
     * Delete user by ID
     *
     * @param int $id
     * @return bool | AdminUser
     */
    public function deleteById(int $id): bool | AdminUser
    {
        $user = $this->getById($id);
        
        if (!$user) {
            return false;
        }
        
        return $this->delete($user);
    }

    /**
     * Create a new user instance
     *
     * @param array<string, mixed> $data
     * @return AdminUser
     */
    public function create(array $data = []): AdminUser
    {
        return $this->createModel($data);
    }

    /**
     * Get user by username
     *
     * @param string $username
     * @return AdminUser|null
     */
    public function getByUsername(string $username): ?AdminUser
    {
        $data = $this->resource->loadByUsername($username);
        
        if (!$data) {
            return null;
        }
        
        return $this->createModel($data);
    }

    /**
     * Get user by email
     *
     * @param string $email
     * @return AdminUser|null
     */
    public function getByEmail(string $email): ?AdminUser
    {
        $data = $this->resource->loadByEmail($email);
        
        if (!$data) {
            return null;
        }
        
        return $this->createModel($data);
    }

    /**
     * Check if username exists
     *
     * @param string $username
     * @return bool
     */
    public function usernameExists(string $username): bool
    {
        return $this->getByUsername($username) !== null;
    }

    /**
     * Check if email exists
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return $this->getByEmail($email) !== null;
    }

    /**
     * Create model instance
     *
     * @param array<string, mixed> $data
     * @return AdminUser
     */
    protected function createModel(array $data = []): AdminUser
    {
        return new AdminUser($this->resource, $data);
    }
}
