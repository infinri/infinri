<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Di;

use SimpleXMLElement;

/**
 * Reads and parses di.xml files to extract dependency injection configuration.
 * Supports: preferences (interface -> implementation), type arguments, virtual types.
 */
class XmlReader
{
    /**
     * Read di.xml file from a module
     *
     * @param string $modulePath Absolute path to module directory
     * @return array<string, mixed>|null DI configuration array or null if not found
     */
    public function read(string $modulePath): ?array
    {
        $diPath = $modulePath . '/etc/di.xml';

        if (!file_exists($diPath)) {
            return null;
        }

        $xml = $this->loadXml($diPath);
        
        if ($xml === null) {
            return null;
        }

        return $this->parseConfig($xml);
    }

    /**
     * Load and validate XML file
     *
     * @param string $filePath
     * @return SimpleXMLElement|null
     */
    private function loadXml(string $filePath): ?SimpleXMLElement
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        
        try {
            $xml = simplexml_load_file($filePath);
            
            if ($xml === false) {
                libxml_clear_errors();
                return null;
            }

            return $xml;
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }
    }

    /**
     * Parse di.xml configuration
     *
     * @param SimpleXMLElement $xml
     * @return array<string, mixed>
     */
    private function parseConfig(SimpleXMLElement $xml): array
    {
        $config = [
            'preferences' => [],
            'types' => [],
            'virtualTypes' => [],
        ];

        // Parse preferences (interface -> implementation mappings)
        if (isset($xml->preference)) {
            foreach ($xml->preference as $preference) {
                $for = (string) $preference['for'];
                $type = (string) $preference['type'];
                
                if ($for && $type) {
                    $config['preferences'][$for] = $type;
                }
            }
        }

        // Parse type configurations
        if (isset($xml->type)) {
            foreach ($xml->type as $type) {
                $typeName = (string) $type['name'];
                
                if (!$typeName) {
                    continue;
                }

                $typeConfig = [];

                // Parse constructor arguments
                if (isset($type->arguments)) {
                    $typeConfig['arguments'] = $this->parseArguments($type->arguments);
                }

                // Parse plugins
                if (isset($type->plugin)) {
                    $typeConfig['plugins'] = $this->parsePlugins($type->plugin);
                }

                $config['types'][$typeName] = $typeConfig;
            }
        }

        // Parse virtual types
        if (isset($xml->virtualType)) {
            foreach ($xml->virtualType as $virtualType) {
                $name = (string) $virtualType['name'];
                $type = (string) $virtualType['type'];
                
                if (!$name || !$type) {
                    continue;
                }

                $virtualConfig = [
                    'type' => $type,
                    'arguments' => [],
                ];

                if (isset($virtualType->arguments)) {
                    $virtualConfig['arguments'] = $this->parseArguments($virtualType->arguments);
                }

                $config['virtualTypes'][$name] = $virtualConfig;
            }
        }

        return $config;
    }

    /**
     * Parse constructor arguments
     *
     * @param SimpleXMLElement $argumentsNode
     * @return array<string, array<string, mixed>>
     */
    private function parseArguments(SimpleXMLElement $argumentsNode): array
    {
        $arguments = [];

        foreach ($argumentsNode->argument as $argument) {
            $name = (string) $argument['name'];
            
            // Access namespaced attribute correctly
            $attrs = $argument->attributes('xsi', true);
            $type = isset($attrs['type']) ? (string) $attrs['type'] : 'string';
            
            if (!$name) {
                continue;
            }

            $value = match ($type) {
                'object' => (string) $argument,
                'string' => (string) $argument,
                'number' => (float) $argument,
                'boolean' => in_array(strtolower((string) $argument), ['true', '1', 'yes']),
                'array' => $this->parseArrayArgument($argument),
                'null' => null,
                default => (string) $argument,
            };

            $arguments[$name] = [
                'type' => $type,
                'value' => $value,
            ];
        }

        return $arguments;
    }

    /**
     * Parse array argument
     *
     * @param SimpleXMLElement $arrayNode
     * @return array<mixed>
     */
    private function parseArrayArgument(SimpleXMLElement $arrayNode): array
    {
        $result = [];

        foreach ($arrayNode->item as $item) {
            $key = (string) ($item['key'] ?? '');
            
            // Access namespaced attribute correctly
            $attrs = $item->attributes('xsi', true);
            $type = isset($attrs['type']) ? (string) $attrs['type'] : 'string';
            
            $value = match ($type) {
                'object' => (string) $item,
                'string' => (string) $item,
                'number' => (float) $item,
                'boolean' => in_array(strtolower((string) $item), ['true', '1', 'yes']),
                'array' => $this->parseArrayArgument($item),
                'null' => null,
                default => (string) $item,
            };

            if ($key !== '') {
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Parse plugins
     *
     * @param SimpleXMLElement $pluginsNode
     * @return array<string, array<string, mixed>>
     */
    private function parsePlugins(SimpleXMLElement $pluginsNode): array
    {
        $plugins = [];

        foreach ($pluginsNode as $plugin) {
            $name = (string) $plugin['name'];
            $type = (string) $plugin['type'];
            $sortOrder = (int) ($plugin['sortOrder'] ?? 10);
            $disabled = strtolower((string) ($plugin['disabled'] ?? 'false')) === 'true';

            if ($name && $type) {
                $plugins[$name] = [
                    'type' => $type,
                    'sortOrder' => $sortOrder,
                    'disabled' => $disabled,
                ];
            }
        }

        return $plugins;
    }

    /**
     * Validate di.xml structure
     *
     * @param string $modulePath
     * @return bool
     */
    public function validate(string $modulePath): bool
    {
        $config = $this->read($modulePath);
        
        return $config !== null;
    }
}
