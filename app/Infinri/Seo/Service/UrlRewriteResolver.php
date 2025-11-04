<?php

declare(strict_types=1);

namespace Infinri\Seo\Service;

use Infinri\Core\Helper\Logger;
use Infinri\Seo\Model\Repository\UrlRewriteRepository;

/**
 * Resolves friendly URLs to internal paths.
 */
class UrlRewriteResolver
{
    public function __construct(
        private readonly UrlRewriteRepository $urlRewriteRepository
    ) {
    }

    /**
     * Resolve a request path to a target path.
     *
     * @param string $requestPath The friendly URL path
     * @param string $storeId     Store ID
     *
     * @return array|null ['target_path' => string, 'redirect_type' => int] or null if not found
     */
    public function resolve(string $requestPath, string $storeId = 'default'): ?array
    {
        // Remove leading/trailing slashes and normalize
        $requestPath = trim($requestPath, '/');

        // Empty path or just slash = homepage, don't rewrite
        if ('' === $requestPath || '/' === $requestPath) {
            return null;
        }

        Logger::debug('Resolving URL rewrite', [
            'request_path' => $requestPath,
            'store_id' => $storeId,
        ]);

        $urlRewrite = $this->urlRewriteRepository->getByRequestPath($requestPath, $storeId);

        if (! $urlRewrite) {
            Logger::debug('No URL rewrite found', ['request_path' => $requestPath]);

            return null;
        }

        Logger::debug('URL rewrite found', [
            'request_path' => $requestPath,
            'target_path' => $urlRewrite->getTargetPath(),
            'redirect_type' => $urlRewrite->getRedirectType(),
        ]);

        return [
            'target_path' => $urlRewrite->getTargetPath(),
            'redirect_type' => $urlRewrite->getRedirectType(),
            'entity_type' => $urlRewrite->getEntityType(),
            'entity_id' => $urlRewrite->getEntityId(),
        ];
    }

    /**
     * Get URL for an entity.
     *
     * @return string|null The friendly URL or null if not found
     */
    public function getUrlForEntity(string $entityType, int $entityId, string $storeId = 'default'): ?string
    {
        $urlRewrite = $this->urlRewriteRepository->getByEntity($entityType, $entityId, $storeId);

        if (! $urlRewrite) {
            return null;
        }

        return '/' . $urlRewrite->getRequestPath();
    }
}
