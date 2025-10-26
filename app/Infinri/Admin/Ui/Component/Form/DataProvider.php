<?php

declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Form;

use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Core\Model\ObjectManager;

/**
 * Admin User Form Data Provider
 * 
 * Note: AdminUser doesn't have a repository, so we override getData to use resource model directly
 */
class DataProvider
{
    public function __construct(
        private readonly string $name,
        private readonly string $primaryFieldName,
        private readonly string $requestFieldName
    ) {
    }

    /**
     * Get form data
     *
     * @param int|null $entityId Entity ID (null for new entity)
     * @return array Form data
     */
    public function getData(?int $entityId = null): array
    {
        // New user - return defaults
        if ($entityId === null) {
            return $this->getDefaultData();
        }

        // Get resource from ObjectManager
        $objectManager = ObjectManager::getInstance();
        $resource = $objectManager->get(AdminUserResource::class);

        // Load existing user
        $userData = $resource->load($entityId);

        if (!$userData) {
            return [];
        }

        // Return user data as array
        return $this->mapEntityToArray($userData);
    }

    protected function getRepositoryClass(): string
    {
        return AdminUserResource::class;
    }

    protected function getDefaultData(): array
    {
        return [
            'user_id' => null,
            'username' => '',
            'email' => '',
            'firstname' => '',
            'lastname' => '',
            'is_active' => true,
        ];
    }

    protected function mapEntityToArray($entity): array
    {
        // Entity is array data from resource model
        return [
            'user_id' => $entity['user_id'] ?? null,
            'username' => $entity['username'] ?? '',
            'email' => $entity['email'] ?? '',
            'firstname' => $entity['firstname'] ?? '',
            'lastname' => $entity['lastname'] ?? '',
            'is_active' => (bool)($entity['is_active'] ?? false),
        ];
    }
}
