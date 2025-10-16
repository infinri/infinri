<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;

/**
 * Header ViewModel
 * 
 * Provides data for the site header template
 */
class Header
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
     * Get logo path
     *
     * @return string Logo file path
     */
    public function getLogo(): string
    {
        return $this->config->getValue('theme/general/logo') ?? 'Infinri_Theme::images/logo.svg';
    }
    
    /**
     * Get home page URL
     *
     * @return string Home URL
     */
    public function getLogoUrl(): string
    {
        return $this->urlBuilder->build('home/index/index');
    }
    
    /**
     * Get main navigation items
     *
     * @return array Navigation menu items
     */
    public function getNavigation(): array
    {
        return [
            [
                'label' => 'Home',
                'url' => $this->urlBuilder->build('home/index/index'),
                'active' => false,
            ],
            [
                'label' => 'About',
                'url' => $this->urlBuilder->build('page/view/about'),
                'active' => false,
            ],
            [
                'label' => 'Products',
                'url' => $this->urlBuilder->build('product/index/index'),
                'active' => false,
            ],
            [
                'label' => 'Contact',
                'url' => $this->urlBuilder->build('contact/index/index'),
                'active' => false,
            ],
        ];
    }
    
    /**
     * Get search form URL
     *
     * @return string Search URL
     */
    public function getSearchUrl(): string
    {
        return $this->urlBuilder->build('search/index/index');
    }
    
    /**
     * Check if search is enabled
     *
     * @return bool True if search enabled
     */
    public function isSearchEnabled(): bool
    {
        return true; // Can be made configurable
    }
    
    /**
     * Get mobile menu label
     *
     * @return string Menu label
     */
    public function getMobileMenuLabel(): string
    {
        return 'Menu';
    }
}
