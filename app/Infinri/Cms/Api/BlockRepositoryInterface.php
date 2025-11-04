<?php

declare(strict_types=1);

namespace Infinri\Cms\Api;

use Infinri\Cms\Model\Block;

/**
 * Provides CRUD operations for CMS blocks (reusable content widgets).
 */
interface BlockRepositoryInterface
{
    /**
     * Get block by ID.
     */
    public function getById(int $blockId): ?Block;

    /**
     * Get block by identifier.
     */
    public function getByIdentifier(string $identifier): ?Block;

    /**
     * Get all blocks.
     *
     * @return Block[]
     */
    public function getAll(bool $activeOnly = false): array;

    /**
     * Save block.
     */
    public function save(Block $block): Block;

    /**
     * Delete block.
     */
    public function delete(int $blockId): bool;
}
