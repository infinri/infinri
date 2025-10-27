<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config\Source;

/**
 * Session Save Method Source Model
 */
class SessionSaveMethod
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'files', 'label' => 'Files'],
            ['value' => 'db', 'label' => 'Database'],
            ['value' => 'redis', 'label' => 'Redis'],
            ['value' => 'memcached', 'label' => 'Memcached'],
        ];
    }
}
