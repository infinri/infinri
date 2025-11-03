<?php

declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Observers respond to events dispatched by the Event Manager
 */
interface ObserverInterface
{
    /**
     * Execute observer
     *
     * @param array<string, mixed> $data Event data
     * @return void
     */
    public function execute(array $data = []): void;
}
