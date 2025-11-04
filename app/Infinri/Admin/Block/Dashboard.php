<?php

declare(strict_types=1);

namespace Infinri\Admin\Block;

use Infinri\Cms\Model\Repository\BlockRepository;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Api\CacheInterface;
use Infinri\Core\Block\Template;
use Infinri\Core\Helper\Logger;
use Infinri\Core\Model\Media\MediaLibrary;
use Infinri\Core\Service\SystemHealthChecker;

/**
 * Provides data for dashboard statistics and quick actions.
 */
class Dashboard extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly BlockRepository $blockRepository,
        private readonly MediaLibrary $mediaLibrary,
        private readonly SystemHealthChecker $healthChecker,
        private readonly CacheInterface $cache,
        array $data = []
    ) {
        // AbstractBlock doesn't have a constructor, so we handle data directly
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }
    }

    /**
     * Get dashboard statistics with real data from repositories.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStatistics(): array
    {
        // Cache statistics for 5 minutes to improve performance
        $cacheKey = 'dashboard_statistics';

        $statistics = $this->cache->get($cacheKey);
        if (null !== $statistics) {
            return $statistics;
        }

        // Get real counts from repositories
        $pageCount = $this->pageRepository->count(true); // Active pages only
        $blockCount = $this->blockRepository->count(true); // Active blocks only
        $mediaCount = $this->getMediaCount();
        $systemHealth = $this->healthChecker->getSimpleStatus();

        $statistics = [
            [
                'title' => 'Pages',
                'value' => (string) $pageCount,
                'label' => 1 === $pageCount ? 'CMS Page' : 'CMS Pages',
                'color' => '#3b82f6',
                'url' => '/admin/cms/page/index',
            ],
            [
                'title' => 'Blocks',
                'value' => (string) $blockCount,
                'label' => 1 === $blockCount ? 'Content Block' : 'Content Blocks',
                'color' => '#8b5cf6',
                'url' => '/admin/cms/block/index',
            ],
            [
                'title' => 'Media',
                'value' => (string) $mediaCount,
                'label' => 1 === $mediaCount ? 'Media File' : 'Media Files',
                'color' => '#ec4899',
                'url' => '/admin/infinri_media/media/index',
            ],
            [
                'title' => 'Status',
                'value' => $systemHealth['value'],
                'label' => $systemHealth['label'],
                'color' => $this->getHealthColor($systemHealth['status']),
                'url' => null, // No URL for system status
            ],
        ];

        // Cache for 5 minutes (300 seconds)
        $this->cache->set($cacheKey, $statistics, 300);

        return $statistics;
    }

    /**
     * Get media file count with error handling.
     */
    private function getMediaCount(): int
    {
        try {
            return $this->mediaLibrary->countFiles();
        } catch (\Exception $e) {
            // Log error and return 0 if media directory is not accessible
            Logger::warning('Dashboard: Unable to count media files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    /**
     * Get color based on system health status.
     */
    private function getHealthColor(string $status): string
    {
        return match ($status) {
            'healthy' => '#10b981',   // Green
            'warning' => '#f59e0b',   // Yellow/Orange
            'critical' => '#ef4444',  // Red
            default => '#6b7280'      // Gray
        };
    }

    /**
     * Get quick action links.
     *
     * @return array<int, array<string, string>>
     */
    public function getQuickActions(): array
    {
        return [
            ['title' => '📄 Manage Pages', 'url' => '/admin/cms/page/index'],
            ['title' => '➕ New Page', 'url' => '/admin/cms/page/edit'],
            ['title' => '🧩 Manage Blocks', 'url' => '/admin/cms/block/index'],
            ['title' => '🖼️ Media Manager', 'url' => '/admin/infinri_media/media/index'],
        ];
    }
}
