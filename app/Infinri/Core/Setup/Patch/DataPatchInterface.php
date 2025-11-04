<?php

declare(strict_types=1);

namespace Infinri\Core\Setup\Patch;

/**
 * Data Patch Interface.
 */
interface DataPatchInterface
{
    /**
     * Apply patch.
     */
    public function apply(): void;

    /**
     * Get patch dependencies
     * Returns array of patch class names that must be applied before this one.
     *
     * @return string[]
     */
    public static function getDependencies(): array;

    /**
     * Get patch aliases (for backwards compatibility).
     *
     * @return string[]
     */
    public function getAliases(): array;
}
