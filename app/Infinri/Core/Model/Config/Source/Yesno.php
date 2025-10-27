<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config\Source;

/**
 * Yes/No Source Model
 */
class Yesno
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '0', 'label' => 'No'],
            ['value' => '1', 'label' => 'Yes'],
        ];
    }
}
