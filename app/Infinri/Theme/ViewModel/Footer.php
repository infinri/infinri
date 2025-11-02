<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;
use Infinri\Menu\ViewModel\Navigation as MenuNavigation;

/**
 * Footer ViewModel
 * 
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
        private ScopeConfig $config,
        private UrlBuilder $urlBuilder,
        private MenuNavigation $menuNavigation
    ) {}
    
    /**
     * Get copyright text
     *
     * @return string Copyright notice
     */
    public function getCopyright(): string
    {
        $year = date('Y');
        $copyright = $this->config->getValue('theme/general/copyright');
        
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
     * Get social media links
     *
     * @return array Social media platforms
     */
    public function getSocialLinks(): array
    {
        return [
            [
                'platform' => 'Twitter',
                'label' => 'Twitter',
                'url' => 'https://twitter.com/infinri',
                'icon' => 'twitter',
            ],
            [
                'platform' => 'GitHub',
                'label' => 'GitHub',
                'url' => 'https://github.com/infinri',
                'icon' => 'github',
            ],
            [
                'platform' => 'LinkedIn',
                'label' => 'LinkedIn',
                'url' => 'https://linkedin.com/company/infinri',
                'icon' => 'linkedin',
            ],
        ];
    }
    
    /**
     * Get newsletter subscription enabled status
     *
     * @return bool True if newsletter enabled
     */
    public function isNewsletterEnabled(): bool
    {
        return (bool)$this->config->getValue('theme/footer/newsletter_enabled');
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
