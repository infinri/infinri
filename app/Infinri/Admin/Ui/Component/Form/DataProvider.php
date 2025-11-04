<?php

declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Form;

use Infinri\Admin\Model\Repository\AdminUserRepository;
use Infinri\Core\Model\ObjectManager;

/**
 * Admin User Form Data Provider.
 */
class DataProvider
{
    /**
     * Get form data.
     *
     * @param int|null $entityId Entity ID (null for new entity)
     *
     * @return array<string, mixed> Form data
     */
    public function getData(?int $entityId = null): array
    {
        // New user - return defaults
        if (null === $entityId) {
            return $this->getDefaultData();
        }

        // Get repository from ObjectManager
        $objectManager = ObjectManager::getInstance();
        $repository = $objectManager->get(AdminUserRepository::class);

        // Load existing user
        $user = $repository->getById($entityId);

        if (! $user) {
            return [];
        }

        return [
            'user_id' => $user->getData('user_id'),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'firstname' => $user->getData('firstname'),
            'lastname' => $user->getData('lastname'),
            'is_active' => (bool) $user->getData('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
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
}
