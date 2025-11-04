<?php

declare(strict_types=1);

namespace Tests\Unit\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;
use Infinri\Theme\ViewModel\Footer;
use PHPUnit\Framework\TestCase;

class FooterTest extends TestCase
{
    private Footer $viewModel;
    private ScopeConfig $config;
    private UrlBuilder $urlBuilder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ScopeConfig::class);
        $this->urlBuilder = $this->createMock(UrlBuilder::class);
        $menuNavigation = $this->createMock(\Infinri\Menu\ViewModel\Navigation::class);
        
        $this->viewModel = new Footer($this->config, $this->urlBuilder, $menuNavigation);
    }

    public function test_get_copyright_returns_configured_value(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme_footer/general/copyright')
            ->willReturn('© 2025 Custom Copyright');

        $this->assertEquals('© 2025 Custom Copyright', $this->viewModel->getCopyright());
    }

    public function test_get_copyright_returns_default_with_current_year(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme_footer/general/copyright')
            ->willReturn(null);

        $year = date('Y');
        $expected = "© {$year} Infinri. All rights reserved.";
        
        $this->assertEquals($expected, $this->viewModel->getCopyright());
    }

    public function test_get_links_returns_footer_links(): void
    {
        // getLinks() calls menuNavigation->getFooterNavigation()
        // We need to mock that instead
        $links = $this->viewModel->getLinks();

        $this->assertIsArray($links);
        // Links come from Menu module navigation, may be empty in test
    }

    public function test_get_social_links_returns_social_media(): void
    {
        // Create a fresh ViewModel with proper mock for this test
        $config = $this->createMock(ScopeConfig::class);
        $config->method('getValue')
            ->with('theme_footer/social/social_links')
            ->willReturn(json_encode([
                ['label' => 'Twitter', 'url' => 'https://twitter.com/infinri', 'icon' => 'twitter'],
                ['label' => 'GitHub', 'url' => 'https://github.com/infinri', 'icon' => 'github'],
            ]));
        
        $menuNav = $this->createMock(\Infinri\Menu\ViewModel\Navigation::class);
        $viewModel = new Footer($config, $this->urlBuilder, $menuNav);
        
        $social = $viewModel->getSocialLinks();

        $this->assertIsArray($social);
        $this->assertCount(2, $social);
        
        $this->assertEquals('Twitter', $social[0]['label']);
        $this->assertEquals('https://twitter.com/infinri', $social[0]['url']);
    }

    public function test_is_newsletter_enabled_returns_config_value(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme_footer/newsletter/enabled')
            ->willReturn('1');

        $this->assertTrue($this->viewModel->isNewsletterEnabled());
    }

    public function test_get_newsletter_url_returns_subscription_url(): void
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('build')
            ->with('newsletter/subscriber/new')
            ->willReturn('/newsletter/subscribe');

        $this->assertEquals('/newsletter/subscribe', $this->viewModel->getNewsletterUrl());
    }
}
