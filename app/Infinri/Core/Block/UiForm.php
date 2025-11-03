<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

use Infinri\Core\View\Element\UiFormRenderer;
use Infinri\Core\Model\ObjectManager;

/**
 * Renders UI component forms within the layout system
 * No template needed - renders directly from UI component XML
 */
class UiForm extends AbstractBlock
{
    private ?UiFormRenderer $uiFormRenderer = null;

    public function __construct(?UiFormRenderer $uiFormRenderer = null)
    {
        $this->uiFormRenderer = $uiFormRenderer;
    }

    /**
     * Get UI Form Renderer (lazy load if not injected)
     */
    private function getRenderer(): UiFormRenderer
    {
        if ($this->uiFormRenderer === null) {
            $objectManager = ObjectManager::getInstance();
            $this->uiFormRenderer = $objectManager->create(UiFormRenderer::class);
        }
        
        return $this->uiFormRenderer;
    }

    /**
     * Render the UI form component
     * Gets form name from block data (set via layout XML arguments)
     */
    public function toHtml(): string
    {
        // Get form name from data (set via layout XML <argument name="component_name">)
        $formName = $this->getData('component_name');
        
        \Infinri\Core\Helper\Logger::debug('UiForm::toHtml called', [
            'form_name' => $formName,
            'all_data' => $this->getData()
        ]);
        
        if (empty($formName)) {
            \Infinri\Core\Helper\Logger::warning('UiForm: No component_name provided');
            return '<div style="padding: 20px; border: 2px solid red;">UiForm Error: No component_name specified in layout XML</div>';
        }

        // Build form params from block data
        $params = [];
        
        // Pass through any data that was set on the block
        foreach ($this->getData() as $key => $value) {
            if ($key !== 'component_name') {
                $params[$key] = $value;
            }
        }
        
        \Infinri\Core\Helper\Logger::debug('UiForm: Rendering form', [
            'form_name' => $formName,
            'params' => $params
        ]);
        
        // Render the form HTML
        try {
            $html = $this->getRenderer()->render($formName, $params);
            \Infinri\Core\Helper\Logger::debug('UiForm: Form rendered', [
                'form_name' => $formName,
                'html_length' => strlen($html)
            ]);
            return $html;
        } catch (\Exception $e) {
            \Infinri\Core\Helper\Logger::error('UiForm: Render failed', [
                'form_name' => $formName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<div style="padding: 20px; border: 2px solid red;">Form Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
