<?php
declare(strict_types=1);

use Infinri\Core\Api\ComponentRegistrarInterface;
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrarInterface::MODULE,
    'Infinri_Core',
    __DIR__
);
