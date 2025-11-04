<?php

declare(strict_types=1);

namespace Infinri\Seo\Block\Adminhtml\Redirect\Button;

/**
 * Delete Button for Redirect Form.
 */
class Delete
{
    /**
     * Get button data.
     */
    public function getButtonData(?int $redirectId = null): array
    {
        if (! $redirectId) {
            return [];
        }

        return [
            'label' => 'Delete',
            'class' => 'delete',
            'on_click' => \sprintf(
                "return confirm('%s');",
                'Are you sure you want to delete this redirect?'
            ),
            'url' => '/admin/seo/redirect/delete?id=' . $redirectId,
            'sort_order' => 20,
        ];
    }
}
