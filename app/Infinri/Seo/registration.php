<?php
/**
 * SEO Module Registration
 */
declare(strict_types=1);

use Infinri\Core\Api\ComponentRegistrarInterface;
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrarInterface::MODULE,
    'Infinri_Seo',
    __DIR__
);
