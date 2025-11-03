<?php

declare(strict_types=1);

namespace Tests\Unit\Admin\Block;

use PHPUnit\Framework\TestCase;
use Infinri\Admin\Block\Dashboard;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Cms\Model\Repository\BlockRepository;
use Infinri\Core\Model\Media\MediaLibrary;
use Infinri\Core\Service\SystemHealthChecker;
use Infinri\Core\Api\CacheInterface;

/**
 * Test dashboard statistics functionality
 */
class DashboardTest extends TestCase
{
    private Dashboard $dashboard;
    private PageRepository $mockPageRepository;
    private BlockRepository $mockBlockRepository;
    private MediaLibrary $mockMediaLibrary;
    private SystemHealthChecker $mockHealthChecker;
    private CacheInterface $mockCache;

    protected function setUp(): void
    {
        $this->mockPageRepository = $this->createMock(PageRepository::class);
        $this->mockBlockRepository = $this->createMock(BlockRepository::class);
        $this->mockMediaLibrary = $this->createMock(MediaLibrary::class);
        $this->mockHealthChecker = $this->createMock(SystemHealthChecker::class);
        $this->mockCache = $this->createMock(CacheInterface::class);

        $this->dashboard = new Dashboard(
            $this->mockPageRepository,
            $this->mockBlockRepository,
            $this->mockMediaLibrary,
            $this->mockHealthChecker,
            $this->mockCache
        );
    }

    public function testGetStatisticsReturnsRealData(): void
    {
        // Mock cache miss
        $this->mockCache->method('get')->willReturn(null);
        $this->mockCache->expects($this->once())->method('set');

        // Mock repository counts
        $this->mockPageRepository->method('count')->willReturn(5);
        $this->mockBlockRepository->method('count')->willReturn(3);
        $this->mockMediaLibrary->method('countFiles')->willReturn(12);

        // Mock system health
        $this->mockHealthChecker->method('getSimpleStatus')->willReturn([
            'value' => '✅',
            'label' => 'System Healthy',
            'status' => 'healthy'
        ]);

        $statistics = $this->dashboard->getStatistics();

        $this->assertIsArray($statistics);
        $this->assertCount(4, $statistics);

        // Check pages statistic
        $this->assertEquals('Pages', $statistics[0]['title']);
        $this->assertEquals('5', $statistics[0]['value']);
        $this->assertEquals('CMS Pages', $statistics[0]['label']);

        // Check blocks statistic
        $this->assertEquals('Blocks', $statistics[1]['title']);
        $this->assertEquals('3', $statistics[1]['value']);
        $this->assertEquals('Content Blocks', $statistics[1]['label']);

        // Check media statistic
        $this->assertEquals('Media', $statistics[2]['title']);
        $this->assertEquals('12', $statistics[2]['value']);
        $this->assertEquals('Media Files', $statistics[2]['label']);

        // Check system status
        $this->assertEquals('Status', $statistics[3]['title']);
        $this->assertEquals('✅', $statistics[3]['value']);
        $this->assertEquals('System Healthy', $statistics[3]['label']);
    }

    public function testGetStatisticsUsesCachedData(): void
    {
        $cachedData = [
            ['title' => 'Pages', 'value' => '10', 'label' => 'CMS Pages', 'color' => '#3b82f6']
        ];

        // Mock cache hit
        $this->mockCache->method('get')->willReturn($cachedData);
        $this->mockCache->expects($this->never())->method('set');

        // Repository methods should not be called when cache hits
        $this->mockPageRepository->expects($this->never())->method('count');
        $this->mockBlockRepository->expects($this->never())->method('count');

        $statistics = $this->dashboard->getStatistics();

        $this->assertEquals($cachedData, $statistics);
    }

    public function testGetStatisticsHandlesSingularLabels(): void
    {
        // Mock cache miss
        $this->mockCache->method('get')->willReturn(null);
        $this->mockCache->expects($this->once())->method('set');

        // Mock single counts
        $this->mockPageRepository->method('count')->willReturn(1);
        $this->mockBlockRepository->method('count')->willReturn(1);
        $this->mockMediaLibrary->method('countFiles')->willReturn(1);

        $this->mockHealthChecker->method('getSimpleStatus')->willReturn([
            'value' => '✅',
            'label' => 'System Healthy',
            'status' => 'healthy'
        ]);

        $statistics = $this->dashboard->getStatistics();

        // Check singular labels
        $this->assertEquals('CMS Page', $statistics[0]['label']);
        $this->assertEquals('Content Block', $statistics[1]['label']);
        $this->assertEquals('Media File', $statistics[2]['label']);
    }

    public function testGetStatisticsHandlesMediaCountError(): void
    {
        // Mock cache miss
        $this->mockCache->method('get')->willReturn(null);
        $this->mockCache->expects($this->once())->method('set');

        $this->mockPageRepository->method('count')->willReturn(2);
        $this->mockBlockRepository->method('count')->willReturn(1);

        // Mock media library throwing exception
        $this->mockMediaLibrary->method('countFiles')
            ->willThrowException(new \Exception('Media directory not accessible'));

        $this->mockHealthChecker->method('getSimpleStatus')->willReturn([
            'value' => '⚠️',
            'label' => 'Minor Issues',
            'status' => 'warning'
        ]);

        $statistics = $this->dashboard->getStatistics();

        // Media count should be 0 when error occurs
        $this->assertEquals('0', $statistics[2]['value']);
    }

    public function testGetQuickActionsReturnsExpectedLinks(): void
    {
        $actions = $this->dashboard->getQuickActions();

        $this->assertIsArray($actions);
        $this->assertCount(4, $actions);

        // Check that all expected actions are present
        $titles = array_column($actions, 'title');
        $this->assertContains('📄 Manage Pages', $titles);
        $this->assertContains('➕ New Page', $titles);
        $this->assertContains('🧩 Manage Blocks', $titles);
        $this->assertContains('🖼️ Media Manager', $titles);

        // Check that all actions have URLs
        foreach ($actions as $action) {
            $this->assertArrayHasKey('url', $action);
            $this->assertNotEmpty($action['url']);
        }
    }

    public function testHealthColorMapping(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->dashboard);
        $method = $reflection->getMethod('getHealthColor');
        $method->setAccessible(true);

        $this->assertEquals('#10b981', $method->invoke($this->dashboard, 'healthy'));
        $this->assertEquals('#f59e0b', $method->invoke($this->dashboard, 'warning'));
        $this->assertEquals('#ef4444', $method->invoke($this->dashboard, 'critical'));
        $this->assertEquals('#6b7280', $method->invoke($this->dashboard, 'unknown'));
    }
}
