<?php
declare(strict_types=1);

namespace Infinri\Core\Setup\Patch;

use PDO;

/**
 * Manages applying data patches and tracking which have been applied
 */
class PatchApplier
{
    private PDO $connection;

    /**
     * Constructor
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->ensurePatchTableExists();
    }

    /**
     * Ensure patch tracking table exists
     */
    private function ensurePatchTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS patch_list (
            patch_id SERIAL PRIMARY KEY,
            patch_name VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->connection->exec($sql);

        // Ensure unique constraint / index for patch_name to support UPSERT semantics
        $this->connection->exec(
            "CREATE UNIQUE INDEX IF NOT EXISTS uq_patch_list_patch_name ON patch_list(patch_name)"
        );
    }

    /**
     * Check if patch has been applied
     */
    public function isApplied(string $patchClass): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM patch_list WHERE patch_name = ?"
        );
        $stmt->execute([$patchClass]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Mark patch as applied
     */
    public function markAsApplied(string $patchClass): void
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO patch_list (patch_name, applied_at) 
             VALUES (?, CURRENT_TIMESTAMP) 
             ON CONFLICT (patch_name) DO UPDATE 
             SET applied_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$patchClass]);
    }

    /**
     * Apply a single patch
     */
    public function applyPatch(DataPatchInterface $patch): void
    {
        $patchClass = get_class($patch);

        if ($this->isApplied($patchClass)) {
            echo "  ⏭️  Skipping (already applied): " . $this->getShortName($patchClass) . "\n";
            return;
        }

        echo "  🔧 Applying: " . $this->getShortName($patchClass) . "...";

        try {
            $patch->apply();
            $this->markAsApplied($patchClass);
            echo " ✅\n";
        } catch (\Exception $e) {
            echo " ❌\n";
            throw new \RuntimeException(
                "Failed to apply patch {$patchClass}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get short patch name for display
     */
    private function getShortName(string $patchClass): string
    {
        $parts = explode('\\', $patchClass);
        return end($parts);
    }

    /**
     * Get list of applied patches
     */
    public function getAppliedPatches(): array
    {
        $stmt = $this->connection->query(
            "SELECT patch_name, applied_at FROM patch_list ORDER BY patch_id"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
