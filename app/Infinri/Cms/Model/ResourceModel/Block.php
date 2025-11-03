<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Handles database operations for CMS blocks.
 */
class Block extends AbstractContentResource
{
    /**
     * Constructor
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Get table name (implements abstract method)
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return 'cms_block';
    }

    /**
     * Get entity ID field name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityIdField(): string
    {
        return 'block_id';
    }

    /**
     * Get unique field name (implements abstract method)
     *
     * @return string
     */
    protected function getUniqueField(): string
    {
        return 'identifier';
    }

    /**
     * Get entity name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityName(): string
    {
        return 'block';
    }

    /**
     * Get block by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getByIdentifier(string $identifier): ?array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM {$this->getMainTable()} WHERE identifier = :identifier LIMIT 1"
        );
        $stmt->execute(['identifier' => $identifier]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
