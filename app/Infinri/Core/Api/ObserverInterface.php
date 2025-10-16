<?php

declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Observer Interface
 * 
 * Observers respond to events dispatched by the Event Manager
 */
interface ObserverInterface
{
    /**
     * Execute observer
     *
     * @param array $data Event data
     * @return void
     */
    public function execute(array $data = []): void;
}
