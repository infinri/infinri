<?php
declare(strict_types=1);

namespace Infinri\Seo\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Redirect Resource Model
 */
class Redirect extends AbstractResource
{
    /**
     * Initialize resource
     */
    protected function _construct(): void
    {
        $this->_init('seo_redirect', 'redirect_id');
    }

    /**
     * Find redirect by from path
     */
    public function findByFromPath(string $fromPath): ?array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->getMainTable()} 
             WHERE from_path = :from_path 
             AND is_active = true 
             ORDER BY priority DESC, redirect_id DESC 
             LIMIT 1"
        );

        $stmt->execute(['from_path' => ltrim($fromPath, '/')]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get all active redirects
     */
    public function getAllActive(): array
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->query(
            "SELECT * FROM {$this->getMainTable()} 
             WHERE is_active = true 
             ORDER BY from_path"
        );

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete redirects by from path
     */
    public function deleteByFromPath(string $fromPath): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare(
            "DELETE FROM {$this->getMainTable()} WHERE from_path = :from_path"
        );

        return $stmt->execute(['from_path' => $fromPath]);
    }
}
