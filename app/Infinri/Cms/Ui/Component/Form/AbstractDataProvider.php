<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Form;

/**
 * Abstract Form Data Provider
 * 
 * Base data provider for UI Component forms
 * Eliminates duplication across Page, Block, and future entity forms
 * 
 * @package Infinri\Cms\Ui\Component\Form
 */
abstract class AbstractDataProvider
{
    /**
     * Constructor parameters kept for interface compatibility
     * (UI Component system may pass these, though currently unused)
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     */
    public function __construct(
        private readonly string $name = '',
        private readonly string $primaryFieldName = 'id',
        private readonly string $requestFieldName = 'id'
    ) {
    }

    // ==================== ABSTRACT METHODS ====================

    /**
     * Get repository class name
     * Each child provider specifies its repository
     *
     * @return string Fully qualified class name
     */
    abstract protected function getRepositoryClass(): string;

    /**
     * Get default data for new entity
     * Each entity has different default values
     *
     * @return array Associative array of field => default_value
     */
    abstract protected function getDefaultData(): array;

    /**
     * Map entity model to array for form
     * Each entity has different fields to edit
     *
     * @param object $entity Entity model instance
     * @return array Associative array of field => value
     */
    abstract protected function mapEntityToArray($entity): array;

    // ==================== COMMON FORM LOGIC ====================

    /**
     * Get form data
     * Returns entity data if ID provided, defaults for new entity
     *
     * @param int|null $entityId Entity ID (null for new entity)
     * @return array Form data
     */
    public function getData(?int $entityId = null): array
    {
        // New entity - return defaults
        if ($entityId === null) {
            return $this->getDefaultData();
        }

        // Get repository from ObjectManager
        $objectManager = \Infinri\Core\Model\ObjectManager::getInstance();
        $repository = $objectManager->get($this->getRepositoryClass());

        // Load existing entity
        $entity = $repository->getById($entityId);

        if (!$entity) {
            return [];
        }

        // Map entity to form array
        return $this->mapEntityToArray($entity);
    }
}
