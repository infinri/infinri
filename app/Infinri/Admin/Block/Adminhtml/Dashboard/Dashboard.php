<?php
declare(strict_types=1);

namespace Infinri\Admin\Block\Adminhtml\Dashboard;

use Infinri\Core\Block\Template;

/**
 * Admin Dashboard Block
 */
class Dashboard extends Template
{
    /**
     * Get logged in user data
     */
    public function getUser(): array
    {
        return $this->getData('user') ?? [];
    }

    /**
     * Get logout URL
     */
    public function getLogoutUrl(): string
    {
        return '/admin/auth/login/logout';
    }
}
