<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Infinri\Core\Api\ObserverInterface;

/**
 * Event Manager
 * 
 * Wrapper around Symfony EventDispatcher for managing application events
 * Provides observer pattern for extending functionality without modifying core code
 */
class Manager
{
    /**
     * Symfony Event Dispatcher
     *
     * @var EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * Registered observers
     *
     * @var array<string, array>
     */
    private array $observers = [];

    /**
     * Constructor
     *
     * @param EventDispatcher|null $dispatcher
     */
    public function __construct(?EventDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?? new EventDispatcher();
    }

    /**
     * Dispatch an event
     *
     * @param string $eventName Event name
     * @param array $data Event data
     * @return void
     */
    public function dispatch(string $eventName, array $data = []): void
    {
        // Call all registered observers for this event
        if (isset($this->observers[$eventName])) {
            foreach ($this->observers[$eventName] as $observerData) {
                $observer = $observerData['instance'];
                if (!$observerData['disabled']) {
                    $observer->execute($data);
                }
            }
        }
    }

    /**
     * Register an observer for an event
     *
     * @param string $eventName Event name
     * @param string $observerName Observer name (for identification)
     * @param ObserverInterface $observer Observer instance
     * @param int $priority Priority (higher = earlier execution)
     * @param bool $disabled Disable the observer
     * @return void
     */
    public function addObserver(
        string $eventName,
        string $observerName,
        ObserverInterface $observer,
        int $priority = 0,
        bool $disabled = false
    ): void {
        if (!isset($this->observers[$eventName])) {
            $this->observers[$eventName] = [];
        }

        $this->observers[$eventName][$observerName] = [
            'instance' => $observer,
            'priority' => $priority,
            'disabled' => $disabled,
        ];

        // Sort by priority (higher priority first)
        uasort($this->observers[$eventName], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Remove an observer
     *
     * @param string $eventName Event name
     * @param string $observerName Observer name
     * @return void
     */
    public function removeObserver(string $eventName, string $observerName): void
    {
        if (isset($this->observers[$eventName][$observerName])) {
            unset($this->observers[$eventName][$observerName]);
        }
    }

    /**
     * Get all observers for an event
     *
     * @param string $eventName Event name
     * @return array Array of observers
     */
    public function getObservers(string $eventName): array
    {
        return $this->observers[$eventName] ?? [];
    }

    /**
     * Check if event has observers
     *
     * @param string $eventName Event name
     * @return bool True if event has observers
     */
    public function hasObservers(string $eventName): bool
    {
        return isset($this->observers[$eventName]) && count($this->observers[$eventName]) > 0;
    }

    /**
     * Clear all observers
     *
     * @return void
     */
    public function clearObservers(): void
    {
        $this->observers = [];
    }

    /**
     * Enable an observer
     *
     * @param string $eventName Event name
     * @param string $observerName Observer name
     * @return void
     */
    public function enableObserver(string $eventName, string $observerName): void
    {
        if (isset($this->observers[$eventName][$observerName])) {
            $this->observers[$eventName][$observerName]['disabled'] = false;
        }
    }

    /**
     * Disable an observer
     *
     * @param string $eventName Event name
     * @param string $observerName Observer name
     * @return void
     */
    public function disableObserver(string $eventName, string $observerName): void
    {
        if (isset($this->observers[$eventName][$observerName])) {
            $this->observers[$eventName][$observerName]['disabled'] = true;
        }
    }

    /**
     * Get Symfony EventDispatcher instance
     *
     * @return EventDispatcher
     */
    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }
}
