<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Event\Config;

use Infinri\Core\Api\ComponentRegistrarInterface;
use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleReader;

/**
 * Reads events.xml from all modules and parses observer configurations.
 */
class Reader
{
    /**
     * Component Registrar.
     */
    private ComponentRegistrarInterface $componentRegistrar;

    /**
     * Module List.
     */
    private ?ModuleList $moduleList = null;

    /**
     * Constructor.
     */
    public function __construct(
        ?ComponentRegistrarInterface $componentRegistrar = null,
        ?ModuleList $moduleList = null
    ) {
        $this->componentRegistrar = $componentRegistrar ?? ComponentRegistrar::getInstance();
        $this->moduleList = $moduleList;
    }

    /**
     * Get module list (lazy initialization).
     */
    private function getModuleList(): ModuleList
    {
        if (null === $this->moduleList) {
            $this->moduleList = new ModuleList(
                ComponentRegistrar::getInstance(),
                new ModuleReader()
            );
        }

        return $this->moduleList;
    }

    /**
     * Read events.xml from a specific module.
     *
     * @param string $moduleName Module name
     *
     * @return array|null Parsed events configuration or null if not found
     */
    public function read(string $moduleName): ?array
    {
        $modulePath = $this->componentRegistrar->getPath(
            ComponentRegistrarInterface::MODULE,
            $moduleName
        );

        if (null === $modulePath) {
            return null;
        }

        $eventsFile = $modulePath . '/etc/events.xml';

        if (! file_exists($eventsFile)) {
            return null;
        }

        return $this->parseXml($eventsFile);
    }

    /**
     * Read events.xml from all modules.
     *
     * @return array Array of events indexed by event name
     */
    public function readAll(): array
    {
        $allEvents = [];
        $modules = $this->getModuleList()->getNames();

        foreach ($modules as $moduleName) {
            $moduleEvents = $this->read($moduleName);

            if (null !== $moduleEvents) {
                // Merge events from this module
                foreach ($moduleEvents as $eventName => $observers) {
                    if (! isset($allEvents[$eventName])) {
                        $allEvents[$eventName] = [];
                    }

                    // Merge observers, later modules can override
                    $allEvents[$eventName] = array_merge($allEvents[$eventName], $observers);
                }
            }
        }

        return $allEvents;
    }

    /**
     * Parse events.xml file.
     *
     * @param string $filePath Path to events.xml
     *
     * @return array Parsed configuration
     */
    private function parseXml(string $filePath): array
    {
        // Suppress XML errors for error handling
        libxml_use_internal_errors(true);

        $xml = @simplexml_load_file($filePath);

        if (false === $xml) {
            libxml_clear_errors();

            return [];
        }

        libxml_clear_errors();

        $events = [];

        // Parse each event
        foreach ($xml->event as $event) {
            $eventName = (string) $event['name'];
            $events[$eventName] = [];

            // Parse observers for this event
            foreach ($event->observer as $observer) {
                $observerName = (string) $observer['name'];
                $events[$eventName][$observerName] = [
                    'instance' => (string) $observer['instance'],
                    'method' => (string) ($observer['method'] ?? 'execute'),
                    'disabled' => filter_var((string) ($observer['disabled'] ?? 'false'), \FILTER_VALIDATE_BOOLEAN),
                ];
            }
        }

        return $events;
    }

    /**
     * Validate events.xml file exists.
     *
     * @param string $moduleName Module name
     *
     * @return bool True if file exists
     */
    public function validate(string $moduleName): bool
    {
        $modulePath = $this->componentRegistrar->getPath(
            ComponentRegistrarInterface::MODULE,
            $moduleName
        );

        if (null === $modulePath) {
            return false;
        }

        return file_exists($modulePath . '/etc/events.xml');
    }
}
