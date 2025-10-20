<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;

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
     */
    public function __construct(
        private ScopeConfig $config,
        private UrlBuilder $urlBuilder
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
     * Get footer links
     *
     * @return array Footer link items
     */
    public function getLinks(): array
    {
        return [
            [
                'label' => 'Privacy Policy',
                'url' => $this->urlBuilder->build('page/view/privacy'),
            ],
            [
                'label' => 'Terms of Service',
                'url' => $this->urlBuilder->build('page/view/terms'),
            ],
            [
                'label' => 'Contact Us',
                'url' => $this->urlBuilder->build('contact/index/index'),
            ],
            [
                'label' => 'About Us',
                'url' => $this->urlBuilder->build('page/view/about'),
            ],
        ];
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
