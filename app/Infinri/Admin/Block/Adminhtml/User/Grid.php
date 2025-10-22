<?php
declare(strict_types=1);

namespace Infinri\Admin\Block\Adminhtml\User;

use Infinri\Core\Block\Template;
use Infinri\Admin\Model\ResourceModel\AdminUser;

/**
 * Admin User Grid Block
 */
class Grid extends Template
{
    public function __construct(
        private readonly AdminUser $adminUserResource
    ) {
        parent::__construct();
    }

    /**
     * Get all admin users
     *
     * @return array
     */
    public function getUsers(): array
    {
        return $this->adminUserResource->findAll();
    }

    /**
     * Format user status
     */
    public function getStatusLabel(bool $isActive): string
    {
        return $isActive ? '<span style="color: green;">✓ Active</span>' : '<span style="color: red;">✗ Inactive</span>';
    }

    /**
     * Format roles
     */
    public function formatRoles(string $rolesJson): string
    {
        $roles = json_decode($rolesJson, true);
        return implode(', ', $roles ?? ['user']);
    }

    /**
     * Format date
     */
    public function formatDate(?string $date): string
    {
        if (!$date) {
            return 'Never';
        }
        return date('M j, Y g:i A', strtotime($date));
    }
}
