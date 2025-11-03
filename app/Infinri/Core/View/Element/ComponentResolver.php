<?php

declare(strict_types=1);

namespace Infinri\Core\View\Element;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Helper\Logger;
use SimpleXMLElement;

/**
 * Handles XML resolution and DataProvider instantiation for UI components
 */
class ComponentResolver
{
    public function __construct(
        private readonly ObjectManager $objectManager
    ) {}

    /**
     * Find UI component XML file
     *
     * @param string $componentName Component name (e.g., 'cms_page_listing')
     * @return string|null Path to XML file or null if not found
     */
    public function findComponentXml(string $componentName): ?string
    {
        // From /app/Infinri/Core/View/Element/, go up 4 levels to /app/
        $appPath = realpath(__DIR__ . '/../../../../');
        if ($appPath === false) {
            throw new \RuntimeException('Failed to get path for: ' . __DIR__ . '/../../../../');
        }

        // Try Cms module first (where cms_page_listing lives)
        $path = $appPath . '/Infinri/Cms/view/adminhtml/ui_component/' . $componentName . '.xml';
        if (file_exists($path)) {
            return $path;
        }

        // Search all modules
        $modules = glob($appPath . '/Infinri/*/view/adminhtml/ui_component/' . $componentName . '.xml');
        return $modules[0] ?? null;
    }

    /**
     * Load XML from file
     *
     * @param string $xmlPath Path to XML file
     * @return SimpleXMLElement|null
     */
    public function loadXml(string $xmlPath): ?SimpleXMLElement
    {
        if (!file_exists($xmlPath)) {
            Logger::error("XML file not found", ['path' => $xmlPath]);
            return null;
        }

        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            Logger::error("Failed to load XML", ['path' => $xmlPath]);
            return null;
        }

        return $xml;
    }

    /**
     * Get data from DataProvider
     *
     * @param SimpleXMLElement $xml Component XML
     * @param array $params Additional parameters (e.g., menu_id)
     * @return array Data from provider
     */
    public function getDataFromProvider(SimpleXMLElement $xml, array $params = []): array
    {
        $providerClass = $this->extractDataProviderClass($xml);

        if (!$providerClass) {
            Logger::warning("No DataProvider found in XML");
            return ['items' => [], 'totalRecords' => 0];
        }

        return $this->instantiateAndFetchData($providerClass, $params);
    }

    /**
     * Extract DataProvider class from XML
     *
     * Supports two patterns:
     * 1. <argument name="dataProvider">Class\Name</argument>
     * 2. <dataProvider class="Class\Name"/>
     *
     * @param SimpleXMLElement $xml
     * @return string|null
     */
    private function extractDataProviderClass(SimpleXMLElement $xml): ?string
    {
        // Try pattern 1: <argument name="dataProvider"> (used by Menu module)
        $dataProviderArg = $xml->xpath('//dataSource/argument[@name="dataProvider"]');

        if (!empty($dataProviderArg)) {
            return (string)$dataProviderArg[0];
        }

        // Try pattern 2: <dataProvider class="..."> (used by CMS module)
        $dataProviderElement = $xml->xpath('//dataSource/dataProvider[@class]');

        if (!empty($dataProviderElement)) {
            return (string)$dataProviderElement[0]['class'];
        }

        return null;
    }

    /**
     * Instantiate DataProvider and fetch data
     *
     * @param string $providerClass
     * @param array $params
     * @return array
     */
    private function instantiateAndFetchData(string $providerClass, array $params): array
    {
        try {
            Logger::info("Creating DataProvider: $providerClass", ['params' => $params]);

            // Create data provider instance (ObjectManager handles DI)
            $provider = $this->objectManager->create($providerClass);

            // Check if getData accepts parameters
            $reflection = new \ReflectionMethod($provider, 'getData');
            $paramCount = $reflection->getNumberOfParameters();

            if ($paramCount > 0) {
                // DataProvider accepts parameters (like menu_id)
                $data = $provider->getData($params);
            } else {
                // DataProvider doesn't accept parameters
                $data = $provider->getData();
            }

            $recordCount = isset($data['totalRecords']) ? $data['totalRecords'] : count($data);
            Logger::info("DataProvider returned $recordCount records");

            return $data;
        } catch (\Throwable $e) {
            Logger::error("DataProvider error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return ['items' => [], 'totalRecords' => 0, 'error' => $e->getMessage()];
        }
    }
}
