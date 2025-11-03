<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Base resource model for all CMS content entities
 * Provides common database operations and validation
 * 
 * @package Infinri\Cms\Model\ResourceModel
 */
abstract class AbstractContentResource extends AbstractResource
{
    /**
     * Constructor
     * Initializes table name and ID field from child class
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->mainTable = $this->getTableName();
        $this->primaryKey = $this->getEntityIdField();
        $this->idFieldName = $this->getEntityIdField();

        parent::__construct($connection);
    }

    /**
     * Get database table name (e.g., 'cms_page', 'cms_block')
     *
     * @return string
     */
    abstract protected function getTableName(): string;

    /**
     * Get entity ID field name (e.g., 'page_id', 'block_id')
     *
     * @return string
     */
    abstract protected function getEntityIdField(): string;

    /**
     * Get unique field name for uniqueness validation (e.g., 'url_key', 'identifier')
     *
     * @return string
     */
    abstract protected function getUniqueField(): string;

    /**
     * Get entity name for error messages (e.g., 'page', 'block')
     *
     * @return string
     */
    abstract protected function getEntityName(): string;

    /**
     * Get all entities
     *
     * @param bool $activeOnly Filter to only active entities
     * @return array
     */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = "SELECT * FROM {$this->getMainTable()}";

        if ($activeOnly) {
            $sql .= " WHERE is_active = true";
        }

        $sql .= " ORDER BY {$this->primaryKey} ASC";

        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if unique field value exists (excluding specified entity ID)
     * Used for validation to prevent duplicates
     *
     * @param string $value Value to check
     * @param int|null $excludeId Entity ID to exclude from check (for updates)
     * @return bool
     */
    protected function uniqueFieldExists(string $value, ?int $excludeId = null): bool
    {
        $field = $this->getUniqueField();
        $sql = "SELECT COUNT(*) FROM {$this->getMainTable()} WHERE {$field} = :{$field}";
        $params = [$field => $value];

        if ($excludeId !== null) {
            $idField = $this->getEntityIdField();
            $sql .= " AND {$idField} != :{$idField}";
            $params[$idField] = $excludeId;
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Before save validation
     * Validates entity and checks uniqueness
     *
     * @param \Infinri\Core\Model\AbstractModel $object
     * @return self
     * @throws \RuntimeException
     */
    protected function _beforeSave(\Infinri\Core\Model\AbstractModel $object): self
    {
        // Validate entity data
        $object->validate();

        // Check unique field uniqueness
        $uniqueField = $this->getUniqueField();
        $uniqueValue = $object->getData($uniqueField);
        $entityId = $object->getData($this->getEntityIdField());

        if ($uniqueValue && $this->uniqueFieldExists($uniqueValue, $entityId)) {
            throw new \RuntimeException(
                sprintf(
                    'A %s with %s "%s" already exists',
                    $this->getEntityName(),
                    $uniqueField,
                    $uniqueValue
                )
            );
        }

        // Set updated timestamp
        $object->setData('updated_at', date('Y-m-d H:i:s'));

        return parent::_beforeSave($object);
    }

    /**
     * Get total count of entities
     * Efficient database count without loading all records
     *
     * @param bool $activeOnly Count only active entities
     * @return int
     */
    public function count(bool $activeOnly = false): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->getMainTable()}";
        $params = [];

        if ($activeOnly) {
            $sql .= " WHERE is_active = :is_active";
            $params['is_active'] = 1;
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Before delete hook
     * Override in child classes to add delete restrictions
     *
     * @param \Infinri\Core\Model\AbstractModel $object
     * @return self
     * @throws \RuntimeException
     */
    protected function _beforeDelete(\Infinri\Core\Model\AbstractModel $object): self
    {
        // Child classes can override to add restrictions
        return parent::_beforeDelete($object);
    }
}
