<?php

declare(strict_types=1);

namespace Infinri\Menu\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Menu\Model\ResourceModel\Menu as MenuResource;

/**
 * Menu Model
 * 
 * Represents a menu container entity (e.g., "Main Navigation", "Footer Links")
 */
class Menu extends AbstractModel
{
    /**
     * Constructor
     *
     * @param MenuResource $resource
     * @param array $data
     */
    public function __construct(
        protected readonly MenuResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get resource model
     *
     * @return MenuResource
     */
    protected function getResource(): MenuResource
    {
        return $this->resource;
    }

    // ==================== GETTERS/SETTERS ====================

    /**
     * Get menu ID
     *
     * @return int|null
     */
    public function getMenuId(): ?int
    {
        return $this->getData('menu_id');
    }

    /**
     * Set menu ID
     *
     * @param int $id
     * @return $this
     */
    public function setMenuId(int $id): self
    {
        return $this->setData('menu_id', $id);
    }

    /**
     * Get identifier
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->getData('identifier');
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): self
    {
        return $this->setData('identifier', $identifier);
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getData('title');
    }

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
    {
        return $this->setData('title', $title);
    }

    /**
     * Check if menu is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set active status
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    // ==================== VALIDATION ====================

    /**
     * Validate menu data
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        $errors = [];

        // Validate title
        if (empty($this->getTitle())) {
            $errors[] = 'Menu title is required';
        }

        // Validate identifier
        $identifier = $this->getIdentifier();
        if (empty($identifier)) {
            $errors[] = 'Menu identifier is required';
        } elseif (!preg_match('/^[a-z0-9_-]+$/', $identifier)) {
            $errors[] = 'Identifier can only contain lowercase letters, numbers, hyphens, and underscores';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'Menu validation failed: ' . implode(', ', $errors)
            );
        }
    }
}
