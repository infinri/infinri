<?php

declare(strict_types=1);

namespace Infinri\Menu\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;

/**
 * Populates main-navigation menu with existing active CMS pages.
 */
class AddCmsPagesToMainMenu implements DataPatchInterface
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly \PDO $connection
    ) {
    }

    public function apply(): void
    {
        // Get main-navigation menu ID
        $stmt = $this->connection->prepare(
            'SELECT menu_id FROM menu WHERE identifier = ? LIMIT 1'
        );
        $stmt->execute(['main-navigation']);
        $menu = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $menu) {
            // Menu doesn't exist yet, skip
            return;
        }

        $menuId = $menu['menu_id'];

        // Get active CMS pages (excluding error pages)
        $stmt = $this->connection->query(
            "SELECT page_id, title, url_key 
             FROM cms_page 
             WHERE is_active::boolean = true 
             AND url_key NOT IN ('404', '500', 'maintenance')
             ORDER BY page_id ASC"
        );
        $pages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($pages)) {
            return;
        }

        $sortOrder = 10;

        foreach ($pages as $page) {
            // Check if menu item already exists for this page
            $stmt = $this->connection->prepare(
                "SELECT item_id FROM menu_item 
                 WHERE menu_id = ? AND link_type = 'cms_page' AND resource_id = ?"
            );
            $stmt->execute([$menuId, $page['page_id']]);

            if ($stmt->fetchColumn()) {
                continue; // Skip if already exists
            }

            // Insert menu item
            $stmt = $this->connection->prepare(
                'INSERT INTO menu_item (
                    menu_id, 
                    parent_item_id, 
                    title, 
                    link_type, 
                    resource_id, 
                    custom_url,
                    css_class,
                    icon_class,
                    open_in_new_tab,
                    sort_order, 
                    is_active, 
                    created_at, 
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );

            $stmt->execute([
                $menuId,
                null,  // parent_item_id (root level)
                $page['title'],
                'cms_page',
                $page['page_id'],
                null,  // custom_url
                null,  // css_class
                null,  // icon_class
                'false', // open_in_new_tab
                $sortOrder,
                'true',   // is_active
            ]);

            $sortOrder += 10;
        }
    }

    public static function getDependencies(): array
    {
        return [
            InstallDefaultMenus::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
