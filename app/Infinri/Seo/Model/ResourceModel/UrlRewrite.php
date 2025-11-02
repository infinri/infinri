<?php
declare(strict_types=1);

namespace Infinri\Seo\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * URL Rewrite Resource Model
 */
class UrlRewrite extends AbstractResource
{
    /**
     * Initialize resource
     */
    protected function _construct(): void
    {
        $this->_init('url_rewrite', 'url_rewrite_id');
    }

    /**
     * Find URL rewrite by request path
     *
     * @param string $requestPath
     * @param string $storeId
     * @return array|null
     */
    public function findByRequestPath(string $requestPath, string $storeId = 'default'): ?array
    {
        $sql = "
            SELECT * FROM url_rewrite 
            WHERE request_path = :request_path 
            AND store_id = :store_id
            LIMIT 1
        ";

        $stmt = $this->connection->getConnection()->prepare($sql);
        $stmt->execute([
            'request_path' => $requestPath,
            'store_id' => $storeId
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find URL rewrite by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @param string $storeId
     * @return array|null
     */
    public function findByEntity(string $entityType, int $entityId, string $storeId = 'default'): ?array
    {
        $sql = "
            SELECT * FROM url_rewrite 
            WHERE entity_type = :entity_type 
            AND entity_id::integer = :entity_id
            AND store_id = :store_id
            LIMIT 1
        ";

        $stmt = $this->connection->getConnection()->prepare($sql);
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'store_id' => $storeId
        ]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all URL rewrites for an entity type
     *
     * @param string $entityType
     * @return array
     */
    public function getAllByEntityType(string $entityType): array
    {
        $sql = "
            SELECT * FROM url_rewrite 
            WHERE entity_type = :entity_type
            ORDER BY request_path ASC
        ";

        $stmt = $this->connection->getConnection()->prepare($sql);
        $stmt->execute(['entity_type' => $entityType]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return bool
     */
    public function deleteByEntity(string $entityType, int $entityId): bool
    {
        $sql = "
            DELETE FROM url_rewrite 
            WHERE entity_type = :entity_type 
            AND entity_id::integer = :entity_id
        ";

        $stmt = $this->connection->getConnection()->prepare($sql);
        return $stmt->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }
}
