<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

use Infinri\Core\Model\View\TemplateResolver;

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
     * @inheritDoc
     */
    public function toHtml(): string
    {
        if (!$this->template) {
            return '';
        }

        // Resolve template file path
        $templateFile = $this->resolveTemplateFile();
        
        if (!$templateFile || !file_exists($templateFile)) {
            return '';
        }

        // Render template
        return $this->renderTemplate($templateFile);
    }

    /**
     * Resolve template file path
     *
     * @return string|null
     */
    private function resolveTemplateFile(): ?string
    {
        if ($this->templateResolver) {
            return $this->templateResolver->resolve($this->template);
        }

        // Fallback: try to resolve manually
        if (str_contains($this->template, '::')) {
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
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Render template file
     *
     * @param string $templateFile
     * @return string
     */
    private function renderTemplate(string $templateFile): string
    {
        // Extract block data as variables
        extract($this->getData() ?: []);
        
        // Make block available in template as $block
        $block = $this;

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
