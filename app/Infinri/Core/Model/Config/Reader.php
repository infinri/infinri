<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use SimpleXMLElement;

/**
 * Config Reader
 * 
 * Reads config.xml files and converts them to arrays.
 */
class Reader
{
    /**
     * Read config.xml file from a module
     *
     * @param string $modulePath Absolute path to module directory
     * @return array<string, mixed>|null Configuration array or null if not found
     */
    public function read(string $modulePath): ?array
    {
        $configPath = $modulePath . '/etc/config.xml';

        if (!file_exists($configPath)) {
            return null;
        }

        $xml = $this->loadXml($configPath);
        
        if ($xml === null) {
            return null;
        }

        return $this->xmlToArray($xml);
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
     * Convert SimpleXMLElement to array recursively
     *
     * @param SimpleXMLElement $xml
     * @return array<string, mixed>|string
     */
    private function xmlToArray(SimpleXMLElement $xml): array|string
    {
        $array = [];

        // Convert attributes
        foreach ($xml->attributes() as $key => $value) {
            $array['@attributes'][$key] = (string) $value;
        }

        // Convert child elements
        foreach ($xml->children() as $key => $child) {
            $key = (string) $key;
            
            // Check if this element has children or only text
            if ($child->count() > 0) {
                // Has children - recurse
                $value = $this->xmlToArray($child);
            } else {
                // No children - get text value
                $value = trim((string) $child);
            }
            
            // Handle multiple elements with same name
            $array[$key] = $this->addArrayValue($array, $key, $value);
        }

        // If only text content and no children/attributes
        if (empty($array) && ($text = trim((string) $xml)) !== '') {
            return $text;
        }

        return $array;
    }

    /**
     * Add value to array, handling multiple elements with same key
     *
     * @param array<string, mixed> $array Current array
     * @param string $key Key to add/append to
     * @param mixed $value Value to add
     * @return mixed Updated value for the key
     */
    private function addArrayValue(array $array, string $key, mixed $value): mixed
    {
        if (isset($array[$key])) {
            // Key already exists - convert to multi-value array
            if (!is_array($array[$key]) || !isset($array[$key][0])) {
                // Not yet a multi-value array, convert it
                return [$array[$key], $value];
            }
            // Already a multi-value array, append
            $array[$key][] = $value;
            return $array[$key];
        }
        
        // Key doesn't exist yet, just set the value
        return $value;
    }

    /**
     * Validate config.xml structure
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
