<?php
declare(strict_types=1);

namespace Infinri\Admin\Ui\Component\Listing;

use Infinri\Admin\Model\ResourceModel\AdminUser;
use Infinri\Core\Model\ObjectManager;

/**
 * Admin User Listing Data Provider
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
     * Get data for grid
     *
     * @return array ['items' => [], 'totalRecords' => int]
     */
    public function getData(): array
    {
        error_log("AdminUser DataProvider::getData() called");
        
        // Get AdminUser resource from ObjectManager (like CMS does with repositories)
        $objectManager = ObjectManager::getInstance();
        $adminUserResource = $objectManager->get(AdminUser::class);
        
        $users = $adminUserResource->findAll();
        error_log("Found " . count($users) . " users from database");
        
        $items = [];
        foreach ($users as $user) {
            $items[] = [
                'user_id' => $user['user_id'] ?? null,
                'username' => $user['username'] ?? '',
                'email' => $user['email'] ?? '',
                'firstname' => $user['firstname'] ?? '',
                'lastname' => $user['lastname'] ?? '',
                'roles' => $this->formatRoles($user['roles'] ?? '[]'),
                'is_active' => (bool)($user['is_active'] ?? false),
                'last_login_at' => $user['last_login_at'] ?? null,
                'created_at' => $user['created_at'] ?? null,
            ];
        }
        
        error_log("Returning " . count($items) . " items to grid");
        
        return [
            'items' => $items,
            'totalRecords' => count($items),
        ];
    }

    private function formatRoles(string $rolesJson): string
    {
        $roles = json_decode($rolesJson, true);
        return implode(', ', $roles ?? ['user']);
    }
}
