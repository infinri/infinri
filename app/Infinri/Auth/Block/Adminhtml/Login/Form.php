<?php
declare(strict_types=1);

namespace Infinri\Auth\Block\Adminhtml\Login;

use Infinri\Core\Block\Template;
use Infinri\Core\Security\CsrfTokenManager;

/**
 * Admin Login Form Block
 */
class Form extends Template
{
    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {}

    /**
     * Get CSRF token for login form
     */
    public function getCsrfToken(): string
    {
        return $this->csrfManager->generateToken('admin_login');
    }

    /**
     * Get form action URL
     */
    public function getFormAction(): string
    {
        return '/admin/auth/login/post';
    }

    /**
     * Get error message based on error code
     */
    public function getErrorMessage(): string
    {
        $error = $this->getData('error');
        
        return match($error) {
            'invalid' => 'Invalid username or password',
            'empty' => 'Please enter username and password',
            'inactive' => 'This account has been disabled',
            'csrf' => 'Security token validation failed. Please try again.',
            'session_invalid' => 'Your session has expired. Please login again.',
            default => ''
        };
    }

    /**
     * Has error?
     */
    public function hasError(): bool
    {
        return !empty($this->getData('error'));
    }
}
