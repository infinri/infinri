<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\ResourceModel;

use Infinri\Cms\Model\Page as PageModel;
use Infinri\Core\Model\AbstractModel;
use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Handles database operations for CMS pages.
 */
class Page extends AbstractContentResource
{
    /**
     * Constructor.
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Get table name (implements abstract method).
     */
    protected function getTableName(): string
    {
        return 'cms_page';
    }

    /**
     * Get entity ID field name (implements abstract method).
     */
    protected function getEntityIdField(): string
    {
        return 'page_id';
    }

    /**
     * Get unique field name (implements abstract method).
     */
    protected function getUniqueField(): string
    {
        return 'url_key';
    }

    /**
     * Get entity name (implements abstract method).
     */
    protected function getEntityName(): string
    {
        return 'page';
    }

    /**
     * Get page by URL key.
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
     * Get homepage.
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
     * Check if page is homepage.
     */
    public function isHomepage(int $pageId): bool
    {
        return PageModel::HOMEPAGE_ID === $pageId;
    }

    /**
     * Before delete validation
     * Override to add homepage protection.
     *
     * @return $this
     *
     * @throws \RuntimeException
     */
    protected function _beforeDelete(AbstractModel $object): self
    {
        /** @var PageModel $object */

        // Prevent homepage deletion
        if ($object->isHomepage()) {
            throw new \RuntimeException('Homepage (page_id=' . PageModel::HOMEPAGE_ID . ') cannot be deleted. The site requires a homepage to function.');
        }

        parent::_beforeDelete($object);

        return $this;
    }
}
