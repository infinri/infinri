<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config\Source;

/**
 * Timezone Source Model
 */
class Timezone
{
    public function toOptionArray(): array
    {
        $timezones = [
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Anchorage' => 'Alaska',
            'Pacific/Honolulu' => 'Hawaii',
            'UTC' => 'UTC',
            'Europe/London' => 'London',
            'Europe/Paris' => 'Paris',
            'Europe/Berlin' => 'Berlin',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Shanghai' => 'Beijing',
            'Asia/Dubai' => 'Dubai',
            'Australia/Sydney' => 'Sydney',
        ];
        
        $options = [];
        foreach ($timezones as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        
        return $options;
    }
}
