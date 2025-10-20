<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

use Infinri\Core\Model\View\TemplateResolver;
use Infinri\Core\Helper\Logger;
use Infinri\Core\Model\ObjectManager;

/**
 * Template Block
 * 
 * A block that renders content from a PHTML template file.
 */
class Template extends AbstractBlock
{
    /**
     * @var string|null Template file path
     */
    private ?string $template = null;

    /**
     * @var TemplateResolver|null Template resolver
     */
    private ?TemplateResolver $templateResolver = null;

    /**
     * @var object|null Layout instance
     */
    protected ?object $layout = null;

    /**
     * @var object|null Cached ViewModel instance
     */
    private ?object $resolvedViewModel = null;
    
    /**
     * @var array<string, string|null> Static cache for resolved template paths
     */
    private static array $templatePathCache = [];

    /**
     * Set template file
     *
     * @param string $template Template path (e.g., 'Infinri_Core::header/logo.phtml')
     * @return $this
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Get template file
     *
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Set template resolver
     *
     * @param TemplateResolver $resolver
     * @return $this
     */
    public function setTemplateResolver(TemplateResolver $resolver): self
    {
        $this->templateResolver = $resolver;
        return $this;
    }

    /**
     * Get template resolver
     *
     * @return TemplateResolver|null
     */
    public function getTemplateResolver(): ?TemplateResolver
    {
        return $this->templateResolver;
    }

    /**
     * Set layout
     *
     * @param object $layout
     * @return void
     */
    public function setLayout(object $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Get layout
     *
     * @return object|null
     */
    public function getLayout(): ?object
    {
        return $this->layout;
    }

    /**
     * Get ViewModel (stub - not yet implemented)
     * 
     * Templates that use ViewModels will get null for now.
     * TODO: Implement ViewModel support
     *
     * @return mixed
     */
    public function getViewModel(): mixed
    {
        if ($this->resolvedViewModel !== null) {
            return $this->resolvedViewModel;
        }

        $viewModel = $this->getData('view_model');

        if (!$viewModel) {
            return null;
        }

        if (is_object($viewModel)) {
            $this->resolvedViewModel = $viewModel;
            return $this->resolvedViewModel;
        }

        if (is_string($viewModel)) {
            try {
                $objectManager = ObjectManager::getInstance();
                $resolved = $objectManager->get($viewModel);

                if (is_object($resolved)) {
                    $this->resolvedViewModel = $resolved;
                    return $this->resolvedViewModel;
                }
            } catch (\Throwable $e) {
                Logger::warning('Template block: Failed to instantiate view model', [
                    'block_name' => $this->getName(),
                    'view_model' => $viewModel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cache the result
        self::$templatePathCache[$this->template] = null;
        
        return null;
    }
    
    /**
     * Clear template path cache (useful for testing)
     *
     * @return void
     */
    public static function clearPathCache(): void
    {
        self::$templatePathCache = [];
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        if (!$this->template) {
            Logger::debug('Template block: No template set', [
                'block_name' => $this->getName()
            ]);
            return '';
        }

        Logger::debug('Template block: Rendering', [
            'block_name' => $this->getName(),
            'template' => $this->template
        ]);

        // Resolve template file path (with caching)
        $templateFile = $this->resolveTemplateFile();
        
        Logger::debug('Template block: Resolved path', [
            'block_name' => $this->getName(),
            'template' => $this->template,
            'resolved_path' => $templateFile,
            'cached' => isset(self::$templatePathCache[$this->template])
        ]);
        
        if (!$templateFile) {
            Logger::warning('Template block: Template file not found', [
                'block_name' => $this->getName(),
                'template' => $this->template,
                'resolved_path' => $templateFile
            ]);
            return '';
        }

        // Render template
        $html = $this->renderTemplate($templateFile);
        
        Logger::debug('Template block: Rendered', [
            'block_name' => $this->getName(),
            'html_length' => strlen($html)
        ]);
        
        return $html;
    }

    /**
     * Resolve template file path
     *
     * @return string|null
     */
    private function resolveTemplateFile(): ?string
    {
        // Check static cache first
        if (isset(self::$templatePathCache[$this->template])) {
            return self::$templatePathCache[$this->template];
        }
        
        $resolvedPath = null;
        
        if ($this->templateResolver) {
            $resolvedPath = $this->templateResolver->resolve($this->template);
        } elseif (str_contains($this->template, '::')) {
            // Fallback: try to resolve manually
            [$moduleName, $filePath] = explode('::', $this->template, 2);
            
            // Try common paths
            $basePath = dirname(__DIR__, 4) . '/app/' . str_replace('_', '/', $moduleName);
            
            $possiblePaths = [
                $basePath . '/view/frontend/templates/' . $filePath,
                $basePath . '/view/templates/' . $filePath,
                $basePath . '/templates/' . $filePath,
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $resolvedPath = $path;
                    break;
                }
            }
        }
        
        // Cache the resolved path (even if null)
        self::$templatePathCache[$this->template] = $resolvedPath;
        
        return $resolvedPath;
    }

    /**
     * Render template file
     *
     * @param string $templateFile
     * @return string
     */
    private function renderTemplate(string $templateFile): string
    {
        // Make block available in template as $block
        $block = $this;
        
        // Extract block data as variables (EXTR_SKIP prevents overwriting $block and $templateFile)
        extract($this->getData() ?: [], EXTR_SKIP);

        // Start output buffering
        ob_start();

        try {
            // Include template file
            include $templateFile;
            
            return ob_get_clean() ?: '';
        } catch (\Throwable $e) {
            ob_end_clean();
            
            // Return error message in development
            return sprintf(
                '<!-- Template Error: %s in %s -->',
                htmlspecialchars($e->getMessage()),
                htmlspecialchars($templateFile)
            );
        }
    }

    /**
     * Escape HTML output
     *
     * @param string $string
     * @return string
     */
    public function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape HTML attribute
     *
     * @param string $string
     * @return string
     */
    public function escapeHtmlAttr(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape URL
     *
     * @param string $url
     * @return string
     */
    public function escapeUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
