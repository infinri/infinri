<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

use Infinri\Core\View\Element\UiComponentRenderer;
use Infinri\Core\Model\ObjectManager;

/**
 * Renders UI components within the layout system
 * No template needed - renders directly from UI component XML
 */
class UiComponent extends AbstractBlock
{
    private ?UiComponentRenderer $uiComponentRenderer = null;

    public function __construct(?UiComponentRenderer $uiComponentRenderer = null)
    {
        $this->uiComponentRenderer = $uiComponentRenderer;
    }

    /**
     * Get UI Component Renderer (lazy load if not injected)
     */
    private function getRenderer(): UiComponentRenderer
    {
        if ($this->uiComponentRenderer === null) {
            $objectManager = ObjectManager::getInstance();
            $this->uiComponentRenderer = $objectManager->create(UiComponentRenderer::class);
        }

        return $this->uiComponentRenderer;
    }

    /**
     * Render the UI component
     * Gets component name from block data (set via layout XML arguments)
     */
    public function toHtml(): string
    {
        // Get component name from data (set via layout XML <argument name="component_name">)
        $componentName = $this->getData('component_name');

        \Infinri\Core\Helper\Logger::debug('UiComponent::toHtml called', [
            'component_name' => $componentName,
            'all_data' => $this->getData()
        ]);

        if (empty($componentName)) {
            \Infinri\Core\Helper\Logger::warning('UiComponent: No component_name provided');
            return '<div style="padding: 20px; border: 2px solid red;">UiComponent Error: No component_name specified in layout XML</div>';
        }

        // Build params from block data (like UiForm does)
        $params = [];
        foreach ($this->getData() as $key => $value) {
            if ($key !== 'component_name') {
                $params[$key] = $value;
            }
        }

        try {
            $html = $this->getRenderer()->render($componentName, $params);
            \Infinri\Core\Helper\Logger::debug('UiComponent: Rendered', [
                'component_name' => $componentName,
                'html_length' => strlen($html)
            ]);
            return $html;
        } catch (\Exception $e) {
            \Infinri\Core\Helper\Logger::error('UiComponent: Render failed', [
                'component_name' => $componentName,
                'error' => $e->getMessage()
            ]);
            return '<div style="padding: 20px; border: 2px solid red;">Component Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
