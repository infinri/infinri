<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use Infinri\Core\Model\ResourceModel\Connection;
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
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->mainTable = 'menu';
        $this->primaryKey = 'menu_id';
        $this->idFieldName = 'menu_id';
    }

    /**
     * Get menu by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getByIdentifier(string $identifier): ?array
    {
        $stmt = $this->connection->getConnection()->prepare(
            "SELECT * FROM {$this->mainTable} WHERE identifier = :identifier LIMIT 1"
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
        $sql = "SELECT * FROM {$this->mainTable}";
        
        if ($activeOnly) {
            $sql .= " WHERE is_active = true";
        }
        
        $sql .= " ORDER BY title ASC";
        
        $stmt = $this->connection->getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
