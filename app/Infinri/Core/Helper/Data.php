<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

/**
 * Data Helper
 * 
 * General utility functions for data manipulation
 */
class Data
{
    /**
     * Check if value is empty (null, empty string, empty array)
     *
     * @param mixed $value Value to check
     * @return bool True if empty
     */
    public function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Get value from array by path with dot notation
     *
     * @param array $array Array to search
     * @param string $path Path (e.g., 'user.address.city')
     * @param mixed $default Default value if not found
     * @return mixed Value or default
     */
    public function getArrayValue(array $array, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (!is_array($array) || !isset($array[$key])) {
                return $default;
            }

            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Set value in array by path with dot notation
     *
     * @param array &$array Array to modify
     * @param string $path Path (e.g., 'user.address.city')
     * @param mixed $value Value to set
     * @return void
     */
    public function setArrayValue(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current = $value;
    }

    /**
     * Convert array to XML
     *
     * @param array $array Array to convert
     * @param string $rootElement Root element name
     * @return string XML string
     */
    public function arrayToXml(array $array, string $rootElement = 'root'): string
    {
        $xml = new \SimpleXMLElement("<{$rootElement}/>");
        $this->arrayToXmlRecursive($array, $xml);

        return $xml->asXML();
    }

    /**
     * Recursive helper for array to XML conversion
     *
     * @param array $array Array data
     * @param \SimpleXMLElement $xml XML element
     * @return void
     */
    private function arrayToXmlRecursive(array $array, \SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild((string)$key);
                $this->arrayToXmlRecursive($value, $child);
            } else {
                $xml->addChild((string)$key, htmlspecialchars((string)$value));
            }
        }
    }

    /**
     * Flatten multi-dimensional array
     *
     * @param array $array Array to flatten
     * @param string $prefix Key prefix
     * @return array Flattened array
     */
    public function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes Bytes
     * @param int $precision Decimal precision
     * @return string Formatted size (e.g., "1.5 MB")
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Generate random string
     *
     * @param int $length String length
     * @param string $characters Characters to use
     * @return string Random string
     */
    public function randomString(int $length = 32, string $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string
    {
        $result = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Truncate string with ellipsis
     *
     * @param string $string String to truncate
     * @param int $length Max length
     * @param string $ellipsis Ellipsis character(s)
     * @return string Truncated string
     */
    public function truncate(string $string, int $length, string $ellipsis = '...'): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length - mb_strlen($ellipsis)) . $ellipsis;
    }

    /**
     * Convert string to slug (URL-friendly)
     *
     * @param string $string String to convert
     * @param string $separator Separator character
     * @return string Slug
     */
    public function slug(string $string, string $separator = '-'): string
    {
        // Convert to lowercase
        $string = mb_strtolower($string);

        // Remove special characters
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);

        // Replace whitespace and multiple separators
        $string = preg_replace('/[\s-]+/', $separator, $string);

        // Trim separators from ends
        return trim($string, $separator);
    }

    /**
     * Check if string starts with substring
     *
     * @param string $haystack String to check
     * @param string $needle Substring to find
     * @return bool True if starts with
     */
    public function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Check if string ends with substring
     *
     * @param string $haystack String to check
     * @param string $needle Substring to find
     * @return bool True if ends with
     */
    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Convert camelCase to snake_case
     *
     * @param string $string String to convert
     * @return string snake_case string
     */
    public function camelToSnake(string $string): string
    {
        // Handle sequences of capital letters
        $string = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $string);
        // Handle normal camelCase
        $string = preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $string);
        return strtolower($string);
    }

    /**
     * Convert snake_case to camelCase
     *
     * @param string $string String to convert
     * @return string camelCase string
     */
    public function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * Deep clone an object
     *
     * @param object $object Object to clone
     * @return object Cloned object
     */
    public function deepClone(object $object): object
    {
        return unserialize(serialize($object));
    }
}
