<?php
declare(strict_types=1);

namespace Infinri\Core\Setup\Patch;

/**
 * Data Patch Interface
 * 
 * Magento-style data patches for seeding/updating data during setup:upgrade
 */
interface DataPatchInterface
{
    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void;
    
    /**
     * Get patch dependencies
     * 
     * Returns array of patch class names that must be applied before this one
     *
     * @return string[]
     */
    public static function getDependencies(): array;
    
    /**
     * Get patch aliases (for backwards compatibility)
     *
     * @return string[]
     */
    public function getAliases(): array;
}
