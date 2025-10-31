<?php

declare(strict_types=1);

namespace Infinri\Menu\Service;

use Infinri\Cms\Model\Repository\PageRepository;

/**
 * Menu Item Resolver
 * 
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
        
        return match($linkType) {
            'cms_page' => $this->resolveCmsPageUrl($item['resource_id'] ?? null),
            'custom_url' => $item['custom_url'] ?? '/',
            'external' => $item['custom_url'] ?? '/',
            // Future: 'category' => $this->resolveCategoryUrl($item['resource_id'] ?? null),
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
            
            return '/cms/page/view?key=' . urlencode($urlKey);
        } catch (\Exception $e) {
            // Log error and return fallback
            error_log('MenuItemResolver: Failed to resolve page URL for ID ' . $pageId . ': ' . $e->getMessage());
            return '/';
        }
    }

    /**
     * Resolve category URL (future implementation)
     *
     * @param int|null $categoryId
     * @return string
     */
    private function resolveCategoryUrl(?int $categoryId): string
    {
        if (!$categoryId) {
            return '/';
        }
        
        // TODO: Implement when Catalog module is added
        // $category = $this->categoryRepository->getById($categoryId);
        // return '/catalog/category/view?id=' . $categoryId;
        
        return '/';
    }
}
