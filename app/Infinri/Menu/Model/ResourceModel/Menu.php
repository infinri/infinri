<?php

declare(strict_types=1);

namespace Infinri\Menu\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Handles database operations for Menu entity.
 */
class Menu extends AbstractResource
{
    /**
     * Constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->mainTable = 'menu';
        $this->primaryKey = 'menu_id';
        $this->idFieldName = 'menu_id';

        parent::__construct($connection);
    }

    /**
     * Get menu by identifier.
     */
    public function getByIdentifier(string $identifier): ?array
    {
        $stmt = $this->connection->getConnection()->prepare(
            "SELECT * FROM {$this->mainTable} WHERE identifier = :identifier LIMIT 1"
        );

        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get all menus.
     */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->mainTable}";

        if ($activeOnly) {
            $sql .= ' WHERE is_active = true';
        }

        $sql .= ' ORDER BY title ASC';

        $stmt = $this->connection->getConnection()->query($sql);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
