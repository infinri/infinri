<?php

declare(strict_types=1);

namespace Infinri\Cms\Ui\Component\Listing;

use Infinri\Cms\Model\Repository\AbstractContentRepository;

/**
 * Abstract Listing Data Provider
 * 
 * Base data provider for UI Component grids
 * Eliminates duplication across Page, Block, and future entity grids
 * 
 * @package Infinri\Cms\Ui\Component\Listing
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
        private readonly string $name,
        private readonly string $primaryFieldName,
        private readonly string $requestFieldName
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
     * Map entity model to array for grid display
     * Each entity has different fields to display
     *
     * @param object $entity Entity model instance
     * @return array Associative array of field => value
     */
    abstract protected function mapEntityToArray($entity): array;

    // ==================== COMMON LISTING LOGIC ====================

    /**
     * Get data for grid
     * Common logic for all listing data providers
     *
     * @return array ['items' => [], 'totalRecords' => int]
     */
    public function getData(): array
    {
        // Get repository from ObjectManager
        $objectManager = \Infinri\Core\Model\ObjectManager::getInstance();
        $repository = $objectManager->get($this->getRepositoryClass());

        // Fetch all entities
        $entities = $repository->getAll();
        $items = [];

        // Map each entity to array format
        foreach ($entities as $entity) {
            $items[] = $this->mapEntityToArray($entity);
        }

        return [
            'items' => $items,
            'totalRecords' => count($items),
        ];
    }
}
