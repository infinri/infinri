<?php
declare(strict_types=1);

namespace Infinri\Core\View\Element;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Helper\Logger;

/**
 * UI Component Renderer
 * 
 * Factory class that orchestrates UI component rendering
 * 
 * Phase 3.2: SOLID Refactoring - Slimmed down to orchestration only
 * - ComponentResolver handles XML and DataProvider logic
 * - GridRenderer handles grid-specific rendering
 * - UiComponentRenderer orchestrates the flow
 */
class UiComponentRenderer
{
    private ComponentResolver $resolver;
    private GridRenderer $gridRenderer;

    public function __construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->resolver = new ComponentResolver($objectManager);
        $this->gridRenderer = new GridRenderer($objectManager);
    }

    /**
     * Render a UI component by name
     * 
     * @param string $componentName Component name (e.g., 'cms_page_listing')
     * @param array $params Additional parameters (e.g., menu_id, filters)
     * @return string Rendered HTML
     */
    public function render(string $componentName, array $params = []): string
    {
        Logger::info("UiComponentRenderer::render START", [
            'component' => $componentName,
            'params' => $params
        ]);
        
        // Find and load XML configuration
        $xmlPath = $this->resolver->findComponentXml($componentName);
        if (!$xmlPath) {
            Logger::error("Component XML not found", ['component' => $componentName]);
            return "<!-- UI Component not found: {$componentName} -->";
        }

        Logger::debug("Found XML at: $xmlPath");

        $xml = $this->resolver->loadXml($xmlPath);
        if (!$xml) {
            return "<!-- Failed to load UI Component XML: {$componentName} -->";
        }

        // Get data from DataProvider
        $data = $this->resolver->getDataFromProvider($xml, $params);

        // Render the grid (Phase 3.2: delegated to GridRenderer)
        return $this->gridRenderer->render($xml, $data, $componentName);
    }
}
