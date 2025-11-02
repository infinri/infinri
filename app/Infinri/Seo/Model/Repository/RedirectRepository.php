<?php
declare(strict_types=1);

namespace Infinri\Seo\Model\Repository;

use Infinri\Seo\Model\Redirect;
use Infinri\Seo\Model\ResourceModel\Redirect as RedirectResource;
use Infinri\Core\Model\ObjectManager;

/**
 * Redirect Repository
 */
class RedirectRepository
{
    public function __construct(
        private RedirectResource $resource,
        private ObjectManager $objectManager
    ) {}

    /**
     * Get redirect by ID
     */
    public function getById(int $id): ?Redirect
    {
        /** @var Redirect $redirect */
        $redirect = $this->objectManager->create(Redirect::class);
        $this->resource->load($redirect, $id);
        
        if (!$redirect->getRedirectId()) {
            return null;
        }
        
        return $redirect;
    }

    /**
     * Get all redirects
     */
    public function getAll(): array
    {
        $pdo = $this->resource->getConnection()->getConnection();
        $stmt = $pdo->query(
            "SELECT * FROM {$this->resource->getMainTable()} ORDER BY priority DESC, from_path"
        );
        
        $redirects = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            /** @var Redirect $redirect */
            $redirect = $this->objectManager->create(Redirect::class, ['data' => $row]);
            $redirects[] = $redirect;
        }
        
        return $redirects;
    }

    /**
     * Get all active redirects
     */
    public function getAllActive(): array
    {
        $rows = $this->resource->getAllActive();
        $redirects = [];
        
        foreach ($rows as $row) {
            /** @var Redirect $redirect */
            $redirect = $this->objectManager->create(Redirect::class, ['data' => $row]);
            $redirects[] = $redirect;
        }
        
        return $redirects;
    }

    /**
     * Find redirect by from path
     */
    public function findByFromPath(string $fromPath): ?Redirect
    {
        $data = $this->resource->findByFromPath($fromPath);
        
        if (!$data) {
            return null;
        }
        
        return $this->objectManager->create(Redirect::class, ['data' => $data]);
    }

    /**
     * Save redirect
     */
    public function save(Redirect $redirect): Redirect
    {
        $this->resource->save($redirect);
        return $redirect;
    }

    /**
     * Delete redirect
     */
    public function delete(Redirect $redirect): bool
    {
        return $this->resource->delete($redirect);
    }

    /**
     * Delete by ID
     */
    public function deleteById(int $id): bool
    {
        $redirect = $this->getById($id);
        if (!$redirect) {
            return false;
        }
        
        return $this->delete($redirect);
    }
}
