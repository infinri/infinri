<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;
use Infinri\Menu\ViewModel\Navigation as MenuNavigation;
use Psr\Cache\InvalidArgumentException;

/**
 * Provides data for the site footer template
 */
class Footer
{
    /**
     * Constructor
     *
     * @param ScopeConfig $config Configuration reader
     * @param UrlBuilder $urlBuilder URL generator
     * @param MenuNavigation $menuNavigation Menu navigation ViewModel
     */
    public function __construct(
        private ScopeConfig    $config,
        private UrlBuilder     $urlBuilder,
        private MenuNavigation $menuNavigation
    ) {}

    /**
     * Get copyright text
     *
     * @return string Copyright notice
     * @throws InvalidArgumentException
     */
    public function getCopyright(): string
    {
        $year = date('Y');
        $copyright = $this->config->getValue('theme_footer/general/copyright');

        return $copyright ?? "© {$year} Infinri. All rights reserved.";
    }

    /**
     * Get footer links from Menu module
     *
     * @return array Footer link items from database
     */
    public function getLinks(): array
    {
        // Load footer links from Menu module (replaces hardcoded links)
        return $this->menuNavigation->getFooterNavigation();
    }

    /**
     * Get social media links from configuration
     *
     * @return array Social media platforms
     * @throws InvalidArgumentException
     */
    public function getSocialLinks(): array
    {
        // Get social links from admin configuration (stored as JSON)
        $socialLinksJson = $this->config->getValue('theme_footer/social/social_links');

        if (empty($socialLinksJson)) {
            return [];
        }

        try {
            $socialLinks = json_decode($socialLinksJson, true, 512, JSON_THROW_ON_ERROR);

            // Validate structure
            if (!is_array($socialLinks)) {
                return [];
            }

            // Filter out invalid entries
            return array_filter($socialLinks, function ($link) {
                return is_array($link)
                    && !empty($link['url'])
                    && !empty($link['label']);
            });

        } catch (\JsonException $e) {
            // Invalid JSON - return empty array
            return [];
        }
    }

    /**
     * Get newsletter subscription enabled status
     *
     * @return bool True if newsletter enabled
     * @throws InvalidArgumentException
     */
    public function isNewsletterEnabled(): bool
    {
        return (bool)$this->config->getValue('theme_footer/newsletter/enabled');
    }

    /**
     * Get newsletter form action URL
     *
     * @return string Newsletter subscription URL
     */
    public function getNewsletterUrl(): string
    {
        return $this->urlBuilder->build('newsletter/subscriber/new');
    }
}
