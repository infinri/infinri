<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use PDO;

/**
 * Menu Resource Model
 * 
 * Handles database operations for Menu entity
 */
class Menu extends AbstractResource
{
    /**
     * Constructor
     *
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        parent::__construct($connection, 'menu', 'menu_id');
    }

    /**
     * Get menu by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getByIdentifier(string $identifier): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE identifier = :identifier LIMIT 1"
        );
        
        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Get all menus
     *
     * @param bool $activeOnly
     * @return array
     */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = true";
        }
        
        $sql .= " ORDER BY title ASC";
        
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
