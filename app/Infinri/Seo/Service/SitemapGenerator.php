<?php
declare(strict_types=1);

namespace Infinri\Seo\Service;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Seo\Model\Repository\UrlRewriteRepository;

/**
 * Generates XML sitemaps for search engines
 */
class SitemapGenerator
{
    private const PRIORITY_HIGH = '1.0';
    private const PRIORITY_MEDIUM = '0.8';
    private const PRIORITY_LOW = '0.5';
    private const CHANGEFREQ_DAILY = 'daily';
    private const CHANGEFREQ_WEEKLY = 'weekly';
    private const CHANGEFREQ_MONTHLY = 'monthly';

    public function __construct(
        private readonly PageRepository       $pageRepository,
        private readonly UrlRewriteRepository $urlRewriteRepository
    ) {}

    /**
     * Generate complete sitemap XML
     *
     * @param string $baseUrl Base URL of the site
     * @return string XML sitemap content
     */
    public function generate(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        // Add homepage
        $this->addUrl($xml, $baseUrl . '/', self::PRIORITY_HIGH, self::CHANGEFREQ_DAILY);

        // Add CMS pages
        $this->addCmsPages($xml, $baseUrl);

        return $xml->asXML();
    }

    /**
     * Add CMS pages to sitemap
     *
     * @param \SimpleXMLElement $xml
     * @param string $baseUrl
     */
    private function addCmsPages(\SimpleXMLElement $xml, string $baseUrl): void
    {
        $pages = $this->pageRepository->getAll();

        foreach ($pages as $page) {
            // Skip disabled pages
            $isActive = $page->getData('is_active');
            if (!$isActive) {
                continue;
            }

            // Skip error pages (404, 500, maintenance)
            $urlKey = $page->getData('url_key');
            if (in_array($urlKey, ['404', '500', 'maintenance'])) {
                continue;
            }

            // Get URL from URL rewrite or use url_key
            $url = $baseUrl . '/' . $urlKey;

            // Determine priority and changefreq based on page
            $priority = ($urlKey === 'home') ? self::PRIORITY_HIGH : self::PRIORITY_MEDIUM;
            $changefreq = ($urlKey === 'home') ? self::CHANGEFREQ_DAILY : self::CHANGEFREQ_WEEKLY;

            // Get last modified date
            $lastmod = $page->getData('update_time') ?? $page->getData('creation_time');

            $this->addUrl($xml, $url, $priority, $changefreq, $lastmod);
        }
    }

    /**
     * Add URL to sitemap
     *
     * @param \SimpleXMLElement $xml
     * @param string $loc URL location
     * @param string $priority Priority (0.0 to 1.0)
     * @param string $changefreq Change frequency
     * @param string|null $lastmod Last modification date
     * @throws \DateMalformedStringException
     */
    private function addUrl(
        \SimpleXMLElement $xml,
        string            $loc,
        string            $priority = self::PRIORITY_MEDIUM,
        string            $changefreq = self::CHANGEFREQ_WEEKLY,
        ?string           $lastmod = null
    ): void
    {
        $urlNode = $xml->addChild('url');
        $urlNode->addChild('loc', htmlspecialchars($loc));
        $urlNode->addChild('priority', $priority);
        $urlNode->addChild('changefreq', $changefreq);

        if ($lastmod) {
            // Format as ISO 8601
            $date = new \DateTime($lastmod);
            $urlNode->addChild('lastmod', $date->format('c'));
        }
    }
}
