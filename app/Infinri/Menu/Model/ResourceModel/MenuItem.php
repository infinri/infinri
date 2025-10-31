<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use PDO;

/**
 * Menu Item Resource Model
 * 
 * Handles database operations for MenuItem entity
 */
class MenuItem extends AbstractResource
{
    /**
     * Constructor
     *
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        parent::__construct($connection, 'menu_item', 'item_id');
    }

    /**
     * Get all menu items for a specific menu
     *
     * @param int $menuId
     * @param bool $activeOnly
     * @return array
     */
    public function getByMenuId(int $menuId, bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE menu_id = :menu_id";
        
        if ($activeOnly) {
            $sql .= " AND is_active = true";
        }
        
        $sql .= " ORDER BY sort_order ASC, item_id ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['menu_id' => $menuId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get menu items by menu identifier (joins with menu table)
     *
     * @param string $identifier
     * @param bool $activeOnly
     * @return array
     */
    public function getByMenuIdentifier(string $identifier, bool $activeOnly = true): array
    {
        $sql = "
            SELECT mi.* 
            FROM {$this->table} mi
            INNER JOIN menu m ON mi.menu_id = m.menu_id
            WHERE m.identifier = :identifier
        ";
        
        if ($activeOnly) {
            $sql .= " AND mi.is_active = true AND m.is_active = true";
        }
        
        $sql .= " ORDER BY mi.sort_order ASC, mi.item_id ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get child items for a parent
     *
     * @param int $parentItemId
     * @param bool $activeOnly
     * @return array
     */
    public function getChildren(int $parentItemId, bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE parent_item_id = :parent_item_id";
        
        if ($activeOnly) {
            $sql .= " AND is_active = true";
        }
        
        $sql .= " ORDER BY sort_order ASC, item_id ASC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['parent_item_id' => $parentItemId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reorder menu items
     *
     * @param array $orderData Array of [item_id => sort_order]
     * @return bool
     */
    public function reorder(array $orderData): bool
    {
        $this->connection->beginTransaction();
        
        try {
            $stmt = $this->connection->prepare(
                "UPDATE {$this->table} SET sort_order = :sort_order WHERE item_id = :item_id"
            );
            
            foreach ($orderData as $itemId => $sortOrder) {
                $stmt->execute([
                    'item_id' => $itemId,
                    'sort_order' => $sortOrder
                ]);
            }
            
            $this->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
