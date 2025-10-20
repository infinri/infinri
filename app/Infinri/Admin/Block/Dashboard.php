<?php

declare(strict_types=1);

namespace Infinri\Admin\Block;

use Infinri\Core\Block\Template;

/**
 * Dashboard Block
 * 
 * Provides data for dashboard statistics and quick actions
 */
class Dashboard extends Template
{
    /**
     * Get dashboard statistics
     */
    public function getStatistics(): array
    {
        // TODO: Get real counts from repositories
        return [
            [
                'title' => 'Pages',
                'value' => '4',
                'label' => 'Total CMS Pages',
                'color' => '#3b82f6'
            ],
            [
                'title' => 'Blocks',
                'value' => '0',
                'label' => 'Content Blocks',
                'color' => '#8b5cf6'
            ],
            [
                'title' => 'Media',
                'value' => '-',
                'label' => 'Uploaded Files',
                'color' => '#ec4899'
            ],
            [
                'title' => 'Status',
                'value' => '✓',
                'label' => 'System Healthy',
                'color' => '#10b981'
            ]
        ];
    }
    
    /**
     * Get quick action links
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
