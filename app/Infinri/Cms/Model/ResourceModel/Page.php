<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Cms\Model\Page as PageModel;

/**
 * CMS Page Resource Model
 * 
 * Handles database operations for CMS pages.
 * Now extends AbstractContentResource for shared functionality.
 */
class Page extends AbstractContentResource
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

    // ==================== REQUIRED ABSTRACT METHODS ====================

    /**
     * Get table name (implements abstract method)
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return 'cms_page';
    }

    /**
     * Get entity ID field name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityIdField(): string
    {
        return 'page_id';
    }

    /**
     * Get unique field name (implements abstract method)
     *
     * @return string
     */
    protected function getUniqueField(): string
    {
        return 'url_key';
    }

    /**
     * Get entity name (implements abstract method)
     *
     * @return string
     */
    protected function getEntityName(): string
    {
        return 'page';
    }

    // ==================== PAGE-SPECIFIC METHODS ====================

    /**
     * Get page by URL key
     *
     * @param string $urlKey
     * @return array|null
     */
    public function getByUrlKey(string $urlKey): ?array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM {$this->getMainTable()} WHERE url_key = :url_key LIMIT 1"
        );
        $stmt->execute(['url_key' => $urlKey]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get homepage
     *
     * @return array|null
     */
    public function getHomepage(): ?array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM {$this->getMainTable()} WHERE is_homepage = true LIMIT 1"
        );
        $stmt->execute();

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Check if page is homepage
     *
     * @param int $pageId
     * @return bool
     */
    public function isHomepage(int $pageId): bool
    {
        return $pageId === PageModel::HOMEPAGE_ID;
    }

    /**
     * Before delete validation
     * Override to add homepage protection
     *
     * @param \Infinri\Core\Model\AbstractModel $object
     * @return self
     * @throws \RuntimeException if trying to delete homepage
     */
    protected function _beforeDelete(\Infinri\Core\Model\AbstractModel $object): self
    {
        /** @var PageModel $object */

        // Prevent homepage deletion
        if ($object->isHomepage()) {
            throw new \RuntimeException(
                'Homepage (page_id=' . PageModel::HOMEPAGE_ID . ') cannot be deleted. ' .
                'The site requires a homepage to function.'
            );
        }

        return parent::_beforeDelete($object);
    }

    // Note: Common methods (getAll, uniqueness checking, validation, timestamps) 
    // are now inherited from AbstractContentResource
}
