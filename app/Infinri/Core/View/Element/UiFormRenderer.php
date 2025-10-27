<?php
declare(strict_types=1);

namespace Infinri\Core\View\Element;

use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Security\CsrfGuard;
use SimpleXMLElement;

/**
 * UI Form Renderer
 * Renders Magento-style UI component forms from XML configuration
 */
class UiFormRenderer
{
    public function __construct(
        private readonly CsrfGuard $csrfGuard
    ) {
    }

    /**
     * Render a UI form by name
     */
    public function render(string $formName, array $params = []): string
    {
        // Load XML configuration
        $xml = $this->findFormXml($formName);
        if (!$xml) {
            return '<p>Form configuration not found: ' . htmlspecialchars($formName) . '</p>';
        }

        // Get form data from DataProvider if specified
        $providerData = $this->getDataFromProvider($xml, $params);

        // Merge with provided data (explicit params override provider data)
        $formData = array_merge($providerData, $params);

        // Build form HTML
        return $this->buildForm($xml, $formData, $formName);
    }

    /**
     * Instantiate and execute the configured data provider for the form.
     */
    private function getDataFromProvider(SimpleXMLElement $xml, array $params): array
    {
        try {
            $dataProviderNode = $xml->xpath('//dataSource/dataProvider')[0] ?? null;
            if (!$dataProviderNode) {
                return [];
            }

            $providerClass = (string)$dataProviderNode['class'];
            if ($providerClass === '') {
                return [];
            }

            $settings = $dataProviderNode->settings ?? null;
            if ($settings === null) {
                return [];
            }

            $objectManager = ObjectManager::getInstance();

            $providerInstance = $objectManager->create($providerClass, [
                'name' => (string)($dataProviderNode['name'] ?? ''),
                'primaryFieldName' => (string)($settings->primaryFieldName ?? ''),
                'requestFieldName' => (string)($settings->requestFieldName ?? ''),
            ]);

            $requestId = $params['id'] ?? null;
            $requestId = $requestId !== null ? (int)$requestId : null;

            if (method_exists($providerInstance, 'getData')) {
                return $providerInstance->getData($requestId);
            }

            return [];
        } catch (\Throwable $e) {
            error_log('Form DataProvider error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Locate and load the form XML definition for the given form name.
     */
    private function findFormXml(string $formName): ?SimpleXMLElement
    {
        // __DIR__ = app/Infinri/Core/View/Element
        // Navigate up to the app directory
        $appPath = realpath(__DIR__ . '/../../../../');
        if ($appPath === false) {
            return null;
        }

        $relativePath = '/view/adminhtml/ui_component/' . $formName . '.xml';

        // Check Cms module first (common location)
        $candidate = $appPath . '/Infinri/Cms' . $relativePath;
        if (file_exists($candidate)) {
            return simplexml_load_file($candidate) ?: null;
        }

        // Fallback: scan all Infinri modules for the form XML
        $matches = glob($appPath . '/Infinri/*' . $relativePath);
        if (!empty($matches)) {
            $path = $matches[0];
            return simplexml_load_file($path) ?: null;
        }

        return null;
    }

    /**
{{ ... }}
     */
    private function buildForm(SimpleXMLElement $xml, array $data, string $formName): string
    {
        error_log("buildForm data: " . json_encode($data));
        
        // Detect entity type from form name
        if (str_contains($formName, 'widget')) {
            $entityType = 'widget';
            $primaryField = 'widget_id';
            $basePath = '/admin/cms/widget';
        } elseif (str_contains($formName, 'block')) {
            $entityType = 'block';
            $primaryField = 'block_id';
            $basePath = '/admin/cms/block';
        } elseif (str_contains($formName, 'user')) {
            $entityType = 'user';
            $primaryField = 'user_id';
            $basePath = '/admin/users';
        } else {
            $entityType = 'page';
            $primaryField = 'page_id';
            $basePath = '/admin/cms/page';
        }
        
        $entityLabel = ucfirst($entityType);
        $primaryId = $data[$primaryField] ?? null;
        $isNew = empty($primaryId);
        
        // Build page title based on entity type
        if ($entityType === 'user') {
            $pageTitle = $isNew ? "New User" : "Edit User: " . ($data['username'] ?? '#' . $primaryId);
        } else {
            $pageTitle = $isNew ? "New $entityLabel" : "Edit $entityLabel: " . ($data['title'] ?? '#' . $primaryId);
        }

        error_log("Form entity: $entityLabel, ID: " . ($primaryId ?? 'null') . ", Title: $pageTitle");

        // Get buttons
        $buttons = $this->getButtons($xml, $primaryId, $basePath);

        // Get fieldsets
        $fieldsets = $this->getFieldsets($xml);

        // Build HTML
        $html = $this->renderFormHtml(
            $pageTitle,
            $buttons,
            $fieldsets,
            $data,
            $isNew,
            $entityType,
            $basePath,
            $primaryField
        );

        return $html;
    }

    /**
     * Get buttons from XML
     */
    private function getButtons(SimpleXMLElement $xml, ?int $entityId, string $basePath): array
    {
        $buttons = [];
        $buttonNodes = $xml->xpath('//settings/buttons/button');

        foreach ($buttonNodes as $node) {
            $name = (string)$node['name'];
            
            // Skip delete button for new pages
            if ($name === 'delete' && !$entityId) {
                continue;
            }
            
            $url = (string)($node->xpath('url')[0]['path'] ?? '#');
            // Replace Magento placeholders
            $normalizedBasePath = rtrim($basePath, '/') . '/';
            $url = str_replace('*/*/', $normalizedBasePath, $url);
            if ($entityId && $name !== 'back') {
                $url .= '?id=' . $entityId;
            }

            $buttons[] = [
                'name' => $name,
                'label' => (string)($node->xpath('label')[0] ?? ucfirst($name)),
                'class' => (string)($node->xpath('class')[0] ?? (string)$node['class'] ?? 'button'),
                'url' => $url,
            ];
        }
        
        return $buttons;
    }

    /**
     * Get fieldsets from XML
     */
    private function getFieldsets(SimpleXMLElement $xml): array
    {
        $fieldsets = [];
        $fieldsetNodes = $xml->xpath('//fieldset');
        
        foreach ($fieldsetNodes as $node) {
            $name = (string)$node['name'];
            $label = (string)($node->xpath('settings/label')[0] ?? ucfirst($name));
            $collapsible = (string)($node->xpath('settings/collapsible')[0] ?? 'false') === 'true';
            $opened = (string)($node->xpath('settings/opened')[0] ?? 'true') === 'true';
            
            $fields = [];
            foreach ($node->xpath('field') as $field) {
                $fields[] = $this->parseField($field);
            }
            
            $fieldsets[] = [
                'name' => $name,
                'label' => $label,
                'collapsible' => $collapsible,
                'opened' => $opened,
                'fields' => $fields,
            ];
        }
        
        return $fieldsets;
    }

    /**
     * Parse field from XML
     */
    private function parseField(SimpleXMLElement $field): array
    {
        $name = (string)$field['name'];
        $formElement = (string)$field['formElement'];
        
        return [
            'name' => $name,
            'type' => $formElement,
            'label' => (string)($field->xpath('settings/label')[0] ?? ucfirst($name)),
            'required' => !empty($field->xpath('settings/validation/rule[@name="required-entry"]')),
            'notice' => (string)($field->xpath('settings/notice')[0] ?? ''),
            'dataScope' => (string)($field->xpath('settings/dataScope')[0] ?? $name),
            'visible' => (string)($field->xpath('settings/visible')[0] ?? 'true') !== 'false',
            'rows' => (int)($field->xpath('formElements/textarea/settings/rows')[0] ?? 5),
        ];
    }

    /**
     * Render form HTML using Theme template
     */
    private function renderFormHtml(
        string $pageTitle,
        array $buttons,
        array $fieldsets,
        array $data,
        bool $isNew,
        string $entityType,
        string $basePath,
        string $primaryField
    ): string
    {
        $csrfField = $this->csrfGuard->getHiddenField($this->buildTokenId($entityType));
        $csrfFieldName = '_csrf_token';
        
        // Template lives in Theme module (presentation layer)
        // __DIR__ is /app/Infinri/Core/View/Element
        // Go up 3 levels to /app/Infinri/, then to Theme
        $templatePath = __DIR__ . '/../../../Theme/view/adminhtml/templates/form.phtml';
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Form template not found at: {$templatePath}");
        }
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function buildTokenId(string $entityType): string
    {
        return 'admin_cms_' . $entityType . '_form';
    }
}
