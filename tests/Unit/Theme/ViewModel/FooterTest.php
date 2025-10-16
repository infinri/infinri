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
        
        $this->viewModel = new Footer($this->config, $this->urlBuilder);
    }

    public function test_get_copyright_returns_configured_value(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme/general/copyright')
            ->willReturn('© 2025 Custom Copyright');

        $this->assertEquals('© 2025 Custom Copyright', $this->viewModel->getCopyright());
    }

    public function test_get_copyright_returns_default_with_current_year(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme/general/copyright')
            ->willReturn(null);

        $year = date('Y');
        $expected = "© {$year} Infinri. All rights reserved.";
        
        $this->assertEquals($expected, $this->viewModel->getCopyright());
    }

    public function test_get_links_returns_footer_links(): void
    {
        $this->urlBuilder
            ->method('build')
            ->willReturnMap([
                ['page/view/privacy', '/privacy'],
                ['page/view/terms', '/terms'],
                ['contact/index/index', '/contact'],
                ['page/view/about', '/about'],
            ]);

        $links = $this->viewModel->getLinks();

        $this->assertIsArray($links);
        $this->assertCount(4, $links);
        
        $this->assertEquals('Privacy Policy', $links[0]['label']);
        $this->assertEquals('/privacy', $links[0]['url']);
    }

    public function test_get_social_links_returns_social_media(): void
    {
        $social = $this->viewModel->getSocialLinks();

        $this->assertIsArray($social);
        $this->assertCount(3, $social);
        
        $this->assertEquals('Twitter', $social[0]['platform']);
        $this->assertEquals('https://twitter.com/infinri', $social[0]['url']);
        $this->assertEquals('twitter', $social[0]['icon']);
    }

    public function test_is_newsletter_enabled_returns_config_value(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme/footer/newsletter_enabled')
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
