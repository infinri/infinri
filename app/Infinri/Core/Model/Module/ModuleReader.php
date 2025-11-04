<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Module;

/**
 * Reads and parses module.xml files to extract module metadata.
 */
class ModuleReader
{
    /**
     * Read module.xml file and extract module information.
     *
     * @param string $modulePath Absolute path to module directory
     *
     * @return array<string, mixed>|null Module data or null if module.xml not found/invalid
     */
    public function read(string $modulePath): ?array
    {
        $moduleXmlPath = $modulePath . '/etc/module.xml';

        if (! file_exists($moduleXmlPath)) {
            return null;
        }

        $xml = $this->loadXml($moduleXmlPath);

        if (null === $xml) {
            return null;
        }

        return $this->parseModuleXml($xml);
    }

    /**
     * Load and validate XML file.
     */
    private function loadXml(string $filePath): ?\SimpleXMLElement
    {
        // Suppress XML errors and handle them manually
        $useInternalErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_file($filePath);

            if (false === $xml) {
                // Log XML errors if needed
                $errors = libxml_get_errors();
                libxml_clear_errors();

                return null;
            }

            return $xml;
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }
    }

    /**
     * Parse module.xml and extract module data.
     *
     * @return array<string, mixed>
     */
    private function parseModuleXml(\SimpleXMLElement $xml): array
    {
        $data = [
            'name' => null,
            'setup_version' => null,
            'sequence' => [],
        ];

        // Get module element
        $moduleElement = $xml->module;

        if (! $moduleElement) {
            return $data;
        }

        // Extract module name
        $data['name'] = isset($moduleElement['name']) ? (string) $moduleElement['name'] : null;

        // Extract setup version (check if attribute exists first)
        if (isset($moduleElement['setup_version'])) {
            $data['setup_version'] = (string) $moduleElement['setup_version'];
        } else {
            $data['setup_version'] = '1.0.0';
        }

        // Extract module dependencies (sequence)
        if (isset($moduleElement->sequence)) {
            foreach ($moduleElement->sequence->module as $dependencyModule) {
                $dependencyName = (string) $dependencyModule['name'];
                if ($dependencyName) {
                    $data['sequence'][] = $dependencyName;
                }
            }
        }

        return $data;
    }

    /**
     * Validate module.xml structure.
     */
    public function validate(string $modulePath): bool
    {
        $data = $this->read($modulePath);

        if (null === $data) {
            return false;
        }

        // Must have a module name
        if (empty($data['name'])) {
            return false;
        }

        return true;
    }
}
