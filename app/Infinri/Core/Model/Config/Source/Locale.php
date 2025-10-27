<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config\Source;

/**
 * Locale Source Model
 */
class Locale
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'en_US', 'label' => 'English (United States)'],
            ['value' => 'en_GB', 'label' => 'English (United Kingdom)'],
            ['value' => 'es_ES', 'label' => 'Spanish (Spain)'],
            ['value' => 'fr_FR', 'label' => 'French (France)'],
            ['value' => 'de_DE', 'label' => 'German (Germany)'],
            ['value' => 'it_IT', 'label' => 'Italian (Italy)'],
            ['value' => 'pt_BR', 'label' => 'Portuguese (Brazil)'],
            ['value' => 'ja_JP', 'label' => 'Japanese (Japan)'],
            ['value' => 'zh_CN', 'label' => 'Chinese (Simplified)'],
        ];
    }
}
