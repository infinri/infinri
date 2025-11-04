<?php

declare(strict_types=1);

namespace Infinri\Seo\Model\Repository;

use Infinri\Seo\Model\ResourceModel\UrlRewrite as UrlRewriteResource;
use Infinri\Seo\Model\UrlRewrite;

/**
 * URL Rewrite Repository.
 */
class UrlRewriteRepository
{
    public function __construct(
        private readonly UrlRewriteResource $resource
    ) {
    }

    /**
     * Get URL rewrite by ID.
     */
    public function getById(int $id): ?UrlRewrite
    {
        $data = $this->resource->load($id);
        if (! $data) {
            return null;
        }

        return new UrlRewrite($this->resource, $data);
    }

    /**
     * Get URL rewrite by request path.
     */
    public function getByRequestPath(string $requestPath, string $storeId = 'default'): ?UrlRewrite
    {
        $data = $this->resource->findByRequestPath($requestPath, $storeId);
        if (! $data) {
            return null;
        }

        return new UrlRewrite($this->resource, $data);
    }

    /**
     * Get URL rewrite by entity.
     */
    public function getByEntity(string $entityType, int $entityId, string $storeId = 'default'): ?UrlRewrite
    {
        $data = $this->resource->findByEntity($entityType, $entityId, $storeId);
        if (! $data) {
            return null;
        }

        return new UrlRewrite($this->resource, $data);
    }

    /**
     * Get all URL rewrites.
     */
    public function getAll(): array
    {
        $data = $this->resource->fetchAll();
        $urlRewrites = [];

        foreach ($data as $row) {
            $urlRewrites[] = new UrlRewrite($this->resource, $row);
        }

        return $urlRewrites;
    }

    /**
     * Get all URL rewrites by entity type.
     */
    public function getAllByEntityType(string $entityType): array
    {
        $data = $this->resource->getAllByEntityType($entityType);
        $urlRewrites = [];

        foreach ($data as $row) {
            $urlRewrites[] = new UrlRewrite($this->resource, $row);
        }

        return $urlRewrites;
    }

    /**
     * Save URL rewrite.
     */
    public function save(UrlRewrite $urlRewrite): UrlRewrite
    {
        $data = $urlRewrite->getData();

        if ($urlRewrite->getUrlRewriteId()) {
            // Update
            $this->resource->update($urlRewrite->getUrlRewriteId(), $data);
        } else {
            // Insert
            $id = $this->resource->insert($data);
            $urlRewrite->setUrlRewriteId($id);
        }

        return $urlRewrite;
    }

    /**
     * Delete URL rewrite.
     */
    public function delete(UrlRewrite $urlRewrite): bool
    {
        if (! $urlRewrite->getUrlRewriteId()) {
            return false;
        }

        return $this->resource->delete($urlRewrite->getUrlRewriteId()) > 0;
    }

    /**
     * Delete by entity.
     */
    public function deleteByEntity(string $entityType, int $entityId): bool
    {
        return $this->resource->deleteByEntity($entityType, $entityId);
    }
}
