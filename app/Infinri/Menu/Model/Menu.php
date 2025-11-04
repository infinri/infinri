<?php

declare(strict_types=1);

namespace Infinri\Menu\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Menu\Model\ResourceModel\Menu as MenuResource;

/**
 * Represents a menu container entity (e.g., "Main Navigation", "Footer Links").
 */
class Menu extends AbstractModel
{
    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        protected readonly MenuResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get resource model.
     */
    protected function getResource(): MenuResource
    {
        return $this->resource;
    }

    /**
     * Get menu ID.
     */
    public function getMenuId(): ?int
    {
        $value = $this->getData('menu_id');

        return null !== $value ? (int) $value : null;
    }

    /**
     * Set menu ID.
     *
     * @return $this
     */
    public function setMenuId(int $id): self
    {
        return $this->setData('menu_id', $id);
    }

    /**
     * Get identifier.
     */
    public function getIdentifier(): ?string
    {
        return $this->getData('identifier');
    }

    /**
     * Set identifier.
     *
     * @return $this
     */
    public function setIdentifier(string $identifier): self
    {
        return $this->setData('identifier', $identifier);
    }

    /**
     * Get title.
     */
    public function getTitle(): ?string
    {
        return $this->getData('title');
    }

    /**
     * Set title.
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        return $this->setData('title', $title);
    }

    /**
     * Check if menu is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set active status.
     *
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get created at timestamp.
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    /**
     * Validate menu data.
     *
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
        } elseif (! preg_match('/^[a-z0-9_-]+$/', $identifier)) {
            $errors[] = 'Identifier can only contain lowercase letters, numbers, hyphens, and underscores';
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException('Menu validation failed: ' . implode(', ', $errors));
        }
    }
}
