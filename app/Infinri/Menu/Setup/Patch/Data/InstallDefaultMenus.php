<?php

declare(strict_types=1);

namespace Infinri\Menu\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;
use PDO;

/**
 * Install Default Menus
 * 
 * Creates default menu containers: main-navigation, footer-links
 */
class InstallDefaultMenus implements DataPatchInterface
{
    /**
     * Constructor
     *
     * @param PDO $connection
     */
    public function __construct(
        private readonly PDO $connection
    ) {}

    /**
     * @inheritDoc
     */
    public function apply(): void
    {
        $menus = $this->getDefaultMenus();
        
        foreach ($menus as $menu) {
            // Check if menu already exists
            $stmt = $this->connection->prepare(
                "SELECT menu_id FROM menu WHERE identifier = ?"
            );
            $stmt->execute([$menu['identifier']]);
            
            if ($stmt->fetchColumn()) {
                continue; // Skip if exists
            }
            
            // Insert menu
            $stmt = $this->connection->prepare(
                "INSERT INTO menu (identifier, title, is_active, created_at, updated_at) 
                 VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );
            
            $stmt->execute([
                $menu['identifier'],
                $menu['title'],
                $menu['is_active'] ? 'true' : 'false'
            ]);
        }
    }

    /**
     * Get default menus data
     *
     * @return array
     */
    private function getDefaultMenus(): array
    {
        return [
            [
                'identifier' => 'main-navigation',
                'title' => 'Main Navigation',
                'is_active' => 1
            ],
            [
                'identifier' => 'footer-links',
                'title' => 'Footer Links',
                'is_active' => 1
            ],
            [
                'identifier' => 'mobile-menu',
                'title' => 'Mobile Menu',
                'is_active' => 1
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
