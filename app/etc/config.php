<?php

declare(strict_types=1);

/**
 * Module Status Configuration
 *
 * Defines which modules are enabled (1) or disabled (0).
 * Modules must be registered AND enabled to be loaded.
 */

return [
    'modules' => [
        'Infinri_Admin' => 1,
        'Infinri_Auth' => 1,
        'Infinri_Cms' => 1,
        'Infinri_Core' => 1,
        'Infinri_Menu' => 1,
        'Infinri_Seo' => 1,
        'Infinri_Theme' => 1,
    ]
];
