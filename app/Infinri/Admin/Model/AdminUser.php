<?php
declare(strict_types=1);

namespace Infinri\Admin\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Admin User Model
 * 
 * Represents an admin user account with Symfony Security integration
 */
class AdminUser extends AbstractModel implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly AdminUserResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AdminUserResource
    {
        return $this->resource;
    }

    // ==================== Symfony Security Interface Methods ====================

    /**
     * Get user identifier (username)
     */
    public function getUserIdentifier(): string
    {
        return $this->getUsername() ?? '';
    }

    /**
     * Get user roles
     */
    public function getRoles(): array
    {
        $roles = $this->getData('roles');
        
        if (is_string($roles)) {
            $roles = json_decode($roles, true);
        }
        
        if (!is_array($roles)) {
            $roles = [];
        }
        
        // Guarantee every user has at least ROLE_USER
        $roles[] = 'ROLE_USER';
        
        return array_unique($roles);
    }

    /**
     * Get password hash
     */
    public function getPassword(): string
    {
        return $this->getData('password') ?? '';
    }

    /**
     * Erase credentials (nothing to do for us)
     */
    public function eraseCredentials(): void
    {
        // No temporary credentials to erase
    }

    // ==================== Admin User Specific Methods ====================

    /**
     * Get user ID
     */
    public function getUserId(): ?int
    {
        return $this->getData('user_id');
    }

    /**
     * Set user ID
     */
    public function setUserId(int $id): self
    {
        return $this->setData('user_id', $id);
    }

    /**
     * Get username
     */
    public function getUsername(): ?string
    {
        return $this->getData('username');
    }

    /**
     * Set username
     */
    public function setUsername(string $username): self
    {
        return $this->setData('username', $username);
    }

    /**
     * Get email
     */
    public function getEmail(): ?string
    {
        return $this->getData('email');
    }

    /**
     * Set email
     */
    public function setEmail(string $email): self
    {
        return $this->setData('email', $email);
    }

    /**
     * Get first name
     */
    public function getFirstname(): ?string
    {
        return $this->getData('firstname');
    }

    /**
     * Set first name
     */
    public function setFirstname(string $firstname): self
    {
        return $this->setData('firstname', $firstname);
    }

    /**
     * Get last name
     */
    public function getLastname(): ?string
    {
        return $this->getData('lastname');
    }

    /**
     * Set last name
     */
    public function setLastname(string $lastname): self
    {
        return $this->setData('lastname', $lastname);
    }

    /**
     * Get full name
     */
    public function getFullName(): string
    {
        $parts = array_filter([
            $this->getFirstname(),
            $this->getLastname()
        ]);
        
        return implode(' ', $parts) ?: $this->getUsername() ?? 'Unknown User';
    }

    /**
     * Set password hash
     */
    public function setPassword(string $password): self
    {
        return $this->setData('password', $password);
    }

    /**
     * Set roles
     */
    public function setRoles(array $roles): self
    {
        return $this->setData('roles', json_encode($roles));
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set active status
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get last update timestamp
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    /**
     * Get last login timestamp
     */
    public function getLastLoginAt(): ?string
    {
        return $this->getData('last_login_at');
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): self
    {
        return $this->setData('last_login_at', date('Y-m-d H:i:s'));
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ROLE_ADMIN');
    }
}
