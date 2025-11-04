<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Form;

/**
 * Base data provider for UI Component forms
 * Eliminates duplication across Page, Block, and future entity forms.
 */
abstract class AbstractDataProvider
{
    /**
     * Constructor parameters kept for interface compatibility
     * (UI Component system may pass these, though currently unused).
     */
    public function __construct(
        private readonly string $name = '',
        private readonly string $primaryFieldName = 'id',
        private readonly string $requestFieldName = 'id'
    ) {
    }

    /**
     * Get component name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get primary field name.
     */
    public function getPrimaryFieldName(): string
    {
        return $this->primaryFieldName;
    }

    /**
     * Get request field name.
     */
    public function getRequestFieldName(): string
    {
        return $this->requestFieldName;
    }

    /**
     * Get repository class name
     * Each child provider specifies its repository.
     *
     * @return string Fully qualified class name
     */
    abstract protected function getRepositoryClass(): string;

    /**
     * Get default data for new entity
     * Each entity has different default values.
     *
     * @return array Associative array of field => default_value
     */
    abstract protected function getDefaultData(): array;

    /**
     * Map entity model to array for form
     * Each entity has different fields to edit.
     *
     * @param object $entity Entity model instance
     *
     * @return array Associative array of field => value
     */
    abstract protected function mapEntityToArray($entity): array;

    /**
     * Get form data
     * Returns entity data if ID provided, defaults for new entity.
     *
     * @param int|null $entityId Entity ID (null for new entity)
     *
     * @return array Form data
     *
     * @throws \Throwable
     */
    public function getData(?int $entityId = null): array
    {
        // New entity - return defaults
        if (null === $entityId) {
            return $this->getDefaultData();
        }

        // Get repository from ObjectManager
        $objectManager = \Infinri\Core\Model\ObjectManager::getInstance();
        /** @var class-string<mixed> $repositoryClass */
        $repositoryClass = $this->getRepositoryClass();
        $repository = $objectManager->get($repositoryClass); // @phpstan-ignore-line

        // Load existing entity
        $entity = $repository->getById($entityId);

        if (! $entity) {
            return [];
        }

        // Map entity to form array
        return $this->mapEntityToArray($entity);
    }
}
