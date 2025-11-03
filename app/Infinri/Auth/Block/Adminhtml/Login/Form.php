<?php
declare(strict_types=1);

namespace Infinri\Auth\Block\Adminhtml\Login;

use Infinri\Core\Block\Template;

/**
 * Admin Login Form Block
 */
class Form extends Template
{
    /**
     * Get form action URL
     */
    public function getFormAction(): string
    {
        return '/admin/auth/login/post';
    }

    /**
     * Get error message from session flash
     */
    public function getErrorMessage(): string
    {
        if (isset($_SESSION['login_error'])) {
            $error = $_SESSION['login_error'];
            unset($_SESSION['login_error']);
            return $error;
        }
        return '';
    }

    /**
     * Has error?
     */
    public function hasError(): bool
    {
        return isset($_SESSION['login_error']);
    }

    /**
     * Get username from previous attempt
     */
    public function getUsername(): string
    {
        if (isset($_SESSION['login_username'])) {
            $username = $_SESSION['login_username'];
            unset($_SESSION['login_username']);
            return $username;
        }
        return '';
    }
}
