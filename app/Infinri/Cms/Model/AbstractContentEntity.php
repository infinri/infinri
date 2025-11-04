<?php

declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Base class for all CMS content entities (Page, Block, Widget, etc.)
 * Provides common functionality and enforces consistent interface.
 */
abstract class AbstractContentEntity extends AbstractModel
{
    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        protected readonly AbstractResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get resource model.
     */
    protected function getResource(): AbstractResource
    {
        return $this->resource;
    }

    /**
     * Get identifier field name (e.g., 'url_key', 'identifier')
     * Used for validation and uniqueness checks.
     */
    abstract protected function getIdentifierField(): string;

    /**
     * Get entity type name (e.g., 'page', 'block')
     * Used for error messages and logging.
     */
    abstract protected function getEntityType(): string;

    /**
     * Get title.
     */
    public function getTitle(): ?string
    {
        return $this->getData('title');
    }

    /**
     * Set title.
     */
    public function setTitle(string $title): self
    {
        return $this->setData('title', $title);
    }

    /**
     * Get content.
     */
    public function getContent(): ?string
    {
        return $this->getData('content');
    }

    /**
     * Set content.
     */
    public function setContent(string $content): self
    {
        return $this->setData('content', $content);
    }

    /**
     * Check if entity is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set active status.
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
     * Validate entity data
     * Combines common validation with entity-specific validation.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        $errors = $this->validateCommonFields();
        $errors = array_merge($errors, $this->validateEntitySpecificFields());

        if (! empty($errors)) {
            throw new \InvalidArgumentException(ucfirst($this->getEntityType()) . ' validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Validate common fields (title, identifier field).
     *
     * @return array List of validation errors
     */
    protected function validateCommonFields(): array
    {
        $errors = [];

        // Validate title
        if (empty($this->getTitle())) {
            $errors[] = ucfirst($this->getEntityType()) . ' title is required';
        }

        // Validate identifier field
        $identifierField = $this->getIdentifierField();
        $identifierValue = $this->getData($identifierField);

        if (empty($identifierValue)) {
            $errors[] = ucfirst($this->getEntityType()) . ' ' . $identifierField . ' is required';
        } elseif (! preg_match('/^[a-z0-9_-]+$/', $identifierValue)) {
            $errors[] = ucfirst($identifierField) . ' can only contain lowercase letters, numbers, hyphens, and underscores';
        }

        return $errors;
    }

    /**
     * Validate entity-specific fields
     * Override this method to add custom validation.
     *
     * @return array List of validation errors
     */
    protected function validateEntitySpecificFields(): array
    {
        return [];
    }
}
