<?php
declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Listing;

use Infinri\Admin\Model\Repository\AdminUserRepository;
use Infinri\Core\Model\ObjectManager;

/**
 * Admin User Listing Data Provider
 */
class DataProvider
{
    /**
     * Get data for grid
     *
     * @return array ['items' => [], 'totalRecords' => int]
     */
    public function getData(): array
    {
        // Get repository from ObjectManager
        $objectManager = ObjectManager::getInstance();
        $repository = $objectManager->get(AdminUserRepository::class);

        // Get all users from repository
        $users = $repository->getAll();

        $items = [];
        foreach ($users as $user) {
            $items[] = [
                'user_id' => $user->getData('user_id'),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstname' => $user->getData('firstname'),
                'lastname' => $user->getData('lastname'),
                'roles' => implode(', ', $user->getRoles()),
                'is_active' => (bool)$user->getData('is_active'),
                'last_login_at' => $user->getData('last_login_at'),
                'created_at' => $user->getData('created_at'),
            ];
        }

        return [
            'items' => $items,
            'totalRecords' => count($items),
        ];
    }
}
