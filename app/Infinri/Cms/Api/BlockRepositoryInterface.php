<?php

declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Block;

/**
 * CMS Block Repository Interface
 * 
 * Provides CRUD operations for CMS blocks (reusable content widgets)
 */
interface BlockRepositoryInterface
{
    /**
     * Get block by ID
     *
     * @param int $blockId
     * @return Block|null
     */
    public function getById(int $blockId): ?Block;
    
    /**
     * Get block by identifier
     *
     * @param string $identifier
     * @return Block|null
     */
    public function getByIdentifier(string $identifier): ?Block;
    
    /**
     * Get all blocks
     *
     * @param bool $activeOnly
     * @return Block[]
     */
    public function getAll(bool $activeOnly = false): array;
    
    /**
     * Save block
     *
     * @param Block $block
     * @return Block
     */
    public function save(Block $block): Block;
    
    /**
     * Delete block
     *
     * @param int $blockId
     * @return bool
     */
    public function delete(int $blockId): bool;
}
