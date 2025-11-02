<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config\Source;

/**
 * Yes/No Source Model
 * Provides boolean options for configuration fields
 */
class YesNo
{
    /**
     * Options array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => 'Yes'],
            ['value' => '0', 'label' => 'No'],
        ];
    }
}
