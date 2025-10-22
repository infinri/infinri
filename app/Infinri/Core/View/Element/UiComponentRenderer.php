<?php
declare(strict_types=1);

namespace Infinri\Core\View\Element;

use Infinri\Core\Model\ObjectManager;
use SimpleXMLElement;

/**
 * UI Component Renderer
 * Renders Magento-style UI components from XML configuration
 */
class UiComponentRenderer
{
    /**
     * Render a UI component by name
     */
    public function render(string $componentName): string
    {
        // Load XML configuration
        $xmlPath = $this->findComponentXml($componentName);
        if (!$xmlPath || !file_exists($xmlPath)) {
            return "<!-- UI Component not found: {$componentName} -->";
        }

        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            return "<!-- Failed to load UI Component XML: {$componentName} -->";
        }

        // Get data from DataProvider
        $data = $this->getDataFromProvider($xml);

        // Render the grid
        return $this->renderGrid($xml, $data, $componentName);
    }

    /**
     * Find UI component XML file
     */
    private function findComponentXml(string $componentName): ?string
    {
        // From /app/Infinri/Core/View/Element/, go up 4 levels to /app/
        // __DIR__ = /app/Infinri/Core/View/Element/
        // ../../../.. = /app/
        $appPath = realpath(__DIR__ . '/../../../../');
        
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
     * Get data from DataProvider
     */
    private function getDataFromProvider(SimpleXMLElement $xml): array
    {
        $dataSource = $xml->xpath('//dataSource/dataProvider');
        if (empty($dataSource)) {
            return ['items' => [], 'totalRecords' => 0];
        }

        $providerClass = (string)$dataSource[0]['class'];
        if (!$providerClass) {
            return ['items' => [], 'totalRecords' => 0];
        }

        try {
            // Get ObjectManager instance
            $objectManager = ObjectManager::getInstance();
            
            error_log("Creating DataProvider: $providerClass");
            
            // Read DataProvider settings from XML
            $dataProviderNode = $dataSource[0];
            $settings = $dataProviderNode->settings ?? null;
            $name = (string)($dataProviderNode['name'] ?? 'data_source');
            $primaryFieldName = (string)($settings->primaryFieldName ?? 'id');
            $requestFieldName = (string)($settings->requestFieldName ?? 'id');
            
            error_log("DataProvider params: name=$name, primary=$primaryFieldName, request=$requestFieldName");
            
            // Create data provider instance
            $provider = $objectManager->create($providerClass, [
                'name' => $name,
                'primaryFieldName' => $primaryFieldName,
                'requestFieldName' => $requestFieldName
            ]);

            $data = $provider->getData();
            error_log("DataProvider returned: " . json_encode($data));
            
            return $data;
        } catch (\Throwable $e) {
            error_log("DataProvider error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['items' => [], 'totalRecords' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Render grid HTML
     */
    private function renderGrid(SimpleXMLElement $xml, array $data, string $componentName): string
    {
        $items = $data['items'] ?? [];
        $totalRecords = $data['totalRecords'] ?? count($items);

        // Get columns from XML
        $columns = $this->getColumns($xml);
        
        // Get buttons from XML
        $buttons = $this->getButtons($xml);
        
        // Process actions column if defined
        $actionsColumn = $this->getActionsColumn($xml);
        error_log("Actions column object: " . ($actionsColumn ? get_class($actionsColumn) : 'NULL'));
        if ($actionsColumn) {
            $data = ['data' => ['items' => $items], 'totalRecords' => $totalRecords];
            error_log("Before prepareDataSource: " . count($items) . " items");
            $data = $actionsColumn->prepareDataSource($data);
            $items = $data['data']['items'] ?? [];
            error_log("After prepareDataSource: " . count($items) . " items");
        }

        // Build HTML
        $html = '<div class="admin-grid-container">';
        
        // Toolbar with buttons
        if (!empty($buttons)) {
            $html .= '<div class="admin-grid-toolbar">';
            foreach ($buttons as $button) {
                $label = (string)$button['label'];
                $class = (string)($button['class'] ?? 'button primary');
                
                // Generate proper URL - replace Magento placeholders
                $url = (string)$button['url'];
                
                $html .= sprintf(
                    '<a href="%s" class="%s">%s</a>',
                    htmlspecialchars($url),
                    htmlspecialchars($class),
                    htmlspecialchars($label)
                );
            }
            $html .= '</div>';
        }

        // Grid table
        $html .= '<div class="admin-grid" id="' . htmlspecialchars($componentName) . '">';
        $html .= '<table class="admin-grid-table">';
        
        // Header
        $html .= '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead>';
        
        // Body
        $html .= '<tbody>';
        if (empty($items)) {
            $html .= '<tr><td colspan="' . (count($columns) + 1) . '">No records found</td></tr>';
        } else {
            foreach ($items as $item) {
                $html .= '<tr>';
                foreach ($columns as $column) {
                    $value = $item[$column['name']] ?? '';
                    
                    // Format value based on type
                    if ($column['name'] === 'is_active') {
                        $value = $value ? '<span class="status-enabled">Enabled</span>' : '<span class="status-disabled">Disabled</span>';
                    } else {
                        $value = htmlspecialchars((string)$value);
                    }
                    
                    $html .= '<td>' . $value . '</td>';
                }
                
                // Actions column - rendered from prepareDataSource
                $html .= '<td class="actions">';
                if (isset($item['actions']) && is_array($item['actions'])) {
                    $actions = [];
                    foreach ($item['actions'] as $action) {
                        if (isset($action['href']) && isset($action['label'])) {
                            $onclick = isset($action['confirm']) ? 
                                'onclick="return confirm(\'' . htmlspecialchars($action['confirm']['message'] ?? 'Are you sure?') . '\')"' : '';
                            $actions[] = '<a href="' . htmlspecialchars($action['href']) . '" ' . $onclick . '>' . 
                                         htmlspecialchars($action['label']) . '</a>';
                        }
                    }
                    $html .= implode(' | ', $actions);
                } else {
                    $html .= 'No actions';
                }
                $html .= '</td>';
                
                $html .= '</tr>';
            }
        }
        $html .= '</tbody>';
        
        // Footer
        $html .= '<tfoot><tr><td colspan="' . (count($columns) + 1) . '">';
        $html .= 'Total: ' . $totalRecords . ' records';
        $html .= '</td></tr></tfoot>';
        
        $html .= '</table>';
        $html .= '</div>'; // admin-grid
        $html .= '</div>'; // admin-grid-container

        return $html;
    }

    /**
     * Get columns from XML
     */
    private function getColumns(SimpleXMLElement $xml): array
    {
        $columns = [];
        $columnNodes = $xml->xpath('//columns/column');
        
        foreach ($columnNodes as $node) {
            $name = (string)$node['name'];
            if ($name === 'ids') {
                continue; // Skip selection column for now
            }
            
            $label = '';
            $labelNode = $node->xpath('settings/label');
            if (!empty($labelNode)) {
                $label = (string)$labelNode[0];
            }
            
            $columns[] = [
                'name' => $name,
                'label' => $label ?: ucwords(str_replace('_', ' ', $name))
            ];
        }
        
        return $columns;
    }

    /**
     * Get buttons from XML
     */
    private function getButtons(SimpleXMLElement $xml): array
    {
        $buttons = [];
        
        // Try multiple XPath patterns to find buttons
        $buttonNodes = $xml->xpath('//settings/buttons/button');
        if (empty($buttonNodes)) {
            $buttonNodes = $xml->xpath('//button');
        }
        
        error_log("Found " . count($buttonNodes) . " button nodes");
        
        foreach ($buttonNodes as $node) {
            $name = (string)$node['name'];
            
            $urlNode = $node->xpath('url');
            $classNode = $node->xpath('class');
            $labelNode = $node->xpath('label');
            
            $url = !empty($urlNode) ? (string)$urlNode[0]['path'] : '#';
            
            error_log("Button: $name, URL: $url");
            
            $buttons[] = [
                'name' => $name,
                'url' => $url,
                'class' => !empty($classNode) ? (string)$classNode[0] : 'button',
                'label' => !empty($labelNode) ? (string)$labelNode[0] : ucfirst($name)
            ];
        }
        
        return $buttons;
    }

    /**
     * Get and instantiate actions column from XML
     */
    private function getActionsColumn(SimpleXMLElement $xml): ?object
    {
        $actionsNodes = $xml->xpath('//columns/actionsColumn');
        if (empty($actionsNodes)) {
            return null;
        }

        $actionsNode = $actionsNodes[0];
        $className = (string)($actionsNode['class'] ?? '');
        
        if (!$className || !class_exists($className)) {
            error_log("ActionsColumn class not found: $className");
            return null;
        }

        try {
            $objectManager = ObjectManager::getInstance();
            return $objectManager->get($className);
        } catch (\Throwable $e) {
            error_log("Failed to instantiate ActionsColumn: " . $e->getMessage());
            return null;
        }
    }
}
