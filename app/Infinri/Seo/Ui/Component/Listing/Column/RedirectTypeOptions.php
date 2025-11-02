<?php
declare(strict_types=1);

namespace Infinri\Seo\Ui\Component\Listing\Column;

/**
 * Redirect Type Options Source
 */
class RedirectTypeOptions
{
    /**
     * Get options array for redirect types
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 301, 'label' => '301 (Permanent)'],
            ['value' => 302, 'label' => '302 (Temporary)']
        ];
    }
}
