<?php

declare(strict_types=1);

namespace Infinri\Core\View\Element;

use Infinri\Core\Model\ObjectManager;
use SimpleXMLElement;

/**
 * Handles grid-specific rendering logic for UI components
 */
class GridRenderer
{
    public function __construct(
        private readonly ObjectManager $objectManager
    ) {}

    /**
     * Render grid HTML
     *
     * @param SimpleXMLElement $xml Component XML configuration
     * @param array $data Data from DataProvider
     * @param string $componentName Component identifier
     * @return string Rendered HTML
     */
    public function render(SimpleXMLElement $xml, array $data, string $componentName): string
    {
        $items = $data['items'] ?? [];
        $totalRecords = $data['totalRecords'] ?? count($items);

        // Get grid components from XML
        $columns = $this->getColumns($xml);
        $buttons = $this->getButtons($xml);
        $actionsColumn = $this->getActionsColumn($xml);

        // Process actions column if defined
        if ($actionsColumn) {
            $items = $this->prepareActionsData($actionsColumn, $items, $totalRecords);
        }

        // Build HTML
        return $this->buildGridHtml($componentName, $columns, $buttons, $items, $totalRecords);
    }

    /**
     * Get columns from XML
     *
     * @param SimpleXMLElement $xml
     * @return array
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
     *
     * @param SimpleXMLElement $xml
     * @return array
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
     *
     * @param SimpleXMLElement $xml
     * @return object|null
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
            return $this->objectManager->get($className);
        } catch (\Throwable $e) {
            error_log("Failed to instantiate ActionsColumn: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepare actions data using ActionsColumn
     *
     * @param object $actionsColumn
     * @param array $items
     * @param int $totalRecords
     * @return array
     */
    private function prepareActionsData(object $actionsColumn, array $items, int $totalRecords): array
    {
        $data = ['data' => ['items' => $items], 'totalRecords' => $totalRecords];
        error_log("Before prepareDataSource: " . count($items) . " items");

        $data = $actionsColumn->prepareDataSource($data);
        $items = $data['data']['items'] ?? [];

        error_log("After prepareDataSource: " . count($items) . " items");

        return $items;
    }

    /**
     * Build complete grid HTML
     *
     * @param string $componentName
     * @param array $columns
     * @param array $buttons
     * @param array $items
     * @param int $totalRecords
     * @return string
     */
    private function buildGridHtml(
        string $componentName,
        array  $columns,
        array  $buttons,
        array  $items,
        int    $totalRecords
    ): string
    {
        $html = '<div class="admin-grid-container">';

        // Toolbar with buttons
        $html .= $this->renderToolbar($buttons);

        // Grid table
        $html .= '<div class="admin-grid" id="' . htmlspecialchars($componentName) . '">';
        $html .= '<table class="admin-grid-table">';

        // Header
        $html .= $this->renderHeader($columns);

        // Body
        $html .= $this->renderBody($columns, $items);

        // Footer
        $html .= $this->renderFooter($columns, $totalRecords);

        $html .= '</table>';
        $html .= '</div>'; // admin-grid
        $html .= '</div>'; // admin-grid-container

        return $html;
    }

    /**
     * Render toolbar with buttons
     *
     * @param array $buttons
     * @return string
     */
    private function renderToolbar(array $buttons): string
    {
        if (empty($buttons)) {
            return '';
        }

        $html = '<div class="admin-grid-toolbar">';
        foreach ($buttons as $button) {
            $label = (string)$button['label'];
            $class = (string)($button['class'] ?? 'button primary');
            $url = (string)$button['url'];

            $html .= sprintf(
                '<a href="%s" class="%s">%s</a>',
                htmlspecialchars($url),
                htmlspecialchars($class),
                htmlspecialchars($label)
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render table header
     *
     * @param array $columns
     * @return string
     */
    private function renderHeader(array $columns): string
    {
        $html = '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead>';

        return $html;
    }

    /**
     * Render table body
     *
     * @param array $columns
     * @param array $items
     * @return string
     */
    private function renderBody(array $columns, array $items): string
    {
        $html = '<tbody>';

        if (empty($items)) {
            $html .= '<tr><td colspan="' . (count($columns) + 1) . '">No records found</td></tr>';
        } else {
            foreach ($items as $item) {
                $html .= '<tr>';

                // Render column values
                foreach ($columns as $column) {
                    $value = $item[$column['name']] ?? '';
                    $html .= '<td>' . $this->formatColumnValue($column['name'], $value) . '</td>';
                }

                // Render actions
                $html .= '<td class="actions">' . $this->renderActions($item) . '</td>';

                $html .= '</tr>';
            }
        }

        $html .= '</tbody>';

        return $html;
    }

    /**
     * Format column value based on column type
     *
     * @param string $columnName
     * @param mixed $value
     * @return string
     */
    private function formatColumnValue(string $columnName, mixed $value): string
    {
        // Format value based on type
        if ($columnName === 'is_active') {
            return $value ?
                '<span class="status-enabled">Enabled</span>' :
                '<span class="status-disabled">Disabled</span>';
        }

        return htmlspecialchars((string)$value);
    }

    /**
     * Render actions for a row
     *
     * @param array $item
     * @return string
     */
    private function renderActions(array $item): string
    {
        if (!isset($item['actions']) || !is_array($item['actions'])) {
            return 'No actions';
        }

        $actions = [];
        foreach ($item['actions'] as $action) {
            if (isset($action['href']) && isset($action['label'])) {
                $onclick = isset($action['confirm']) ?
                    'onclick="return confirm(\'' . htmlspecialchars($action['confirm']['message'] ?? 'Are you sure?') . '\')"' : '';
                $actions[] = '<a href="' . htmlspecialchars($action['href']) . '" ' . $onclick . '>' .
                    htmlspecialchars($action['label']) . '</a>';
            }
        }

        return implode(' | ', $actions);
    }

    /**
     * Render table footer
     *
     * @param array $columns
     * @param int $totalRecords
     * @return string
     */
    private function renderFooter(array $columns, int $totalRecords): string
    {
        $html = '<tfoot><tr><td colspan="' . (count($columns) + 1) . '">';
        $html .= 'Total: ' . $totalRecords . ' records';
        $html .= '</td></tr></tfoot>';

        return $html;
    }
}
