<?php

declare(strict_types=1);

namespace Tests\Unit\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;
use Infinri\Theme\ViewModel\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    private Header $viewModel;
    private ScopeConfig $config;
    private UrlBuilder $urlBuilder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ScopeConfig::class);
        $this->urlBuilder = $this->createMock(UrlBuilder::class);
        
        $this->viewModel = new Header($this->config, $this->urlBuilder);
    }

    public function test_get_logo_returns_configured_value(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme/general/logo')
            ->willReturn('custom/logo.png');

        $this->assertEquals('custom/logo.png', $this->viewModel->getLogo());
    }

    public function test_get_logo_returns_default_when_not_configured(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getValue')
            ->with('theme/general/logo')
            ->willReturn(null);

        $this->assertEquals('Infinri_Theme::images/logo.svg', $this->viewModel->getLogo());
    }

    public function test_get_logo_url_returns_home_url(): void
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('build')
            ->with('home/index/index')
            ->willReturn('/');

        $this->assertEquals('/', $this->viewModel->getLogoUrl());
    }

    public function test_get_navigation_returns_menu_items(): void
    {
        $this->urlBuilder
            ->method('build')
            ->willReturnMap([
                ['home/index/index', '/'],
                ['page/view/about', '/about'],
                ['product/index/index', '/products'],
                ['contact/index/index', '/contact'],
            ]);

        $navigation = $this->viewModel->getNavigation();

        $this->assertIsArray($navigation);
        $this->assertCount(4, $navigation);
        
        $this->assertEquals('Home', $navigation[0]['label']);
        $this->assertEquals('/', $navigation[0]['url']);
        $this->assertFalse($navigation[0]['active']);

        $this->assertEquals('About', $navigation[1]['label']);
        $this->assertEquals('/about', $navigation[1]['url']);
    }

    public function test_get_search_url_returns_search_route(): void
    {
        $this->urlBuilder
            ->expects($this->once())
            ->method('build')
            ->with('search/index/index')
            ->willReturn('/search');

        $this->assertEquals('/search', $this->viewModel->getSearchUrl());
    }

    public function test_is_search_enabled_returns_true(): void
    {
        $this->assertTrue($this->viewModel->isSearchEnabled());
    }

    public function test_get_mobile_menu_label_returns_menu_text(): void
    {
        $this->assertEquals('Menu', $this->viewModel->getMobileMenuLabel());
    }
}
