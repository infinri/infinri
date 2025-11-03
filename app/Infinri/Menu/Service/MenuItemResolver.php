<?php

declare(strict_types=1);

namespace Infinri\Menu\Service;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Helper\Logger;

/**
 * Resolves URLs for menu items based on link type
 */
class MenuItemResolver
{
    /**
     * Constructor
     *
     * @param PageRepository $pageRepository
     */
    public function __construct(
        private readonly PageRepository $pageRepository
    ) {}

    /**
     * Resolve URL for menu item based on link type
     *
     * @param array $item Menu item data
     * @return string Resolved URL
     */
    public function resolve(array $item): string
    {
        $linkType = $item['link_type'] ?? '';

        // Cast resource_id to int if it exists
        $resourceId = isset($item['resource_id']) && $item['resource_id'] !== null
            ? (int)$item['resource_id']
            : null;

        return match ($linkType) {
            'cms_page' => $this->resolveCmsPageUrl($resourceId),
            'custom_url' => $item['custom_url'] ?? '/',
            'external' => $item['custom_url'] ?? '/',
            default => '/'
        };
    }

    /**
     * Resolve CMS page URL
     *
     * @param int|null $pageId
     * @return string
     */
    private function resolveCmsPageUrl(?int $pageId): string
    {
        if (!$pageId) {
            return '/';
        }

        try {
            $page = $this->pageRepository->getById($pageId);

            if (!$page) {
                return '/';
            }

            $urlKey = $page->getUrlKey();

            // Special handling for homepage
            if ($urlKey === 'home' || $page->isHomepage()) {
                return '/';
            }

            // Use URL rewrite format (/{url_key}) instead of controller format
            return '/' . $urlKey;
        } catch (\Exception $e) {
            // Log error and return fallback
            Logger::warning('MenuItemResolver: Failed to resolve page URL', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ]);
            return '/';
        }
    }
}
