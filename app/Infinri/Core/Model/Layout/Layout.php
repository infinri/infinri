<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Block\AbstractBlock;
use Infinri\Core\Helper\Logger;

/**
 * Layout
 * 
 * Manages layout structure, blocks, and rendering
 * Like Magento's Layout system
 */
class Layout
{
    /**
     * @var array<string> Layout handles
     */
    private array $handles = [];
    
    /**
     * @var array<string, AbstractBlock> Blocks by name
     */
    private array $blocks = [];
    
    /**
     * @var array Layout structure from XML
     */
    private array $structure = [];
    
    /**
     * @var string|null Output content
     */
    private ?string $output = null;
    
    public function __construct(
        private readonly LayoutLoader $layoutLoader,
        private readonly BlockFactory $blockFactory
    ) {
    }
    
    /**
     * Add layout handle
     *
     * @param string $handle
     * @return $this
     */
    public function addHandle(string $handle): self
    {
        if (!in_array($handle, $this->handles, true)) {
            $this->handles[] = $handle;
            Logger::debug("Layout: Added handle {$handle}");
        }
        
        return $this;
    }
    
    /**
     * Get layout handles
     *
     * @return array<string>
     */
    public function getHandles(): array
    {
        return $this->handles;
    }
    
    /**
     * Load layout XML files for all handles
     *
     * @return $this
     */
    public function loadLayout(): self
    {
        Logger::info('Layout: Loading layout', [
            'handles' => $this->handles
        ]);
        
        // Load XML for each handle
        foreach ($this->handles as $handle) {
            $xml = $this->layoutLoader->load($handle);
            
            if ($xml) {
                $this->processLayoutXml($xml);
            }
        }
        
        return $this;
    }
    
    /**
     * Process layout XML and build structure
     *
     * @param \SimpleXMLElement $xml
     * @return void
     */
    private function processLayoutXml(\SimpleXMLElement $xml): void
    {
        // Process <block> elements
        if (isset($xml->body)) {
            $this->processBlockElements($xml->body);
        }
    }
    
    /**
     * Process block elements from XML
     *
     * @param \SimpleXMLElement $element
     * @param string|null $parentName
     * @return void
     */
    private function processBlockElements(\SimpleXMLElement $element, ?string $parentName = null): void
    {
        foreach ($element->block as $blockNode) {
            $name = (string)$blockNode['name'];
            $class = (string)$blockNode['class'];
            $template = (string)($blockNode['template'] ?? '');
            
            Logger::debug("Layout: Creating block {$name}", [
                'class' => $class,
                'template' => $template
            ]);
            
            // Create block instance
            $block = $this->blockFactory->create($class);
            $block->setName($name);
            
            if ($template) {
                $block->setTemplate($template);
            }
            
            // Store block
            $this->blocks[$name] = $block;
            
            // Process child blocks
            if (isset($blockNode->block)) {
                $this->processBlockElements($blockNode, $name);
            }
            
            // Set parent-child relationship
            if ($parentName && isset($this->blocks[$parentName])) {
                $this->blocks[$parentName]->addChild($block);
            }
        }
    }
    
    /**
     * Generate layout output
     *
     * @return $this
     */
    public function generateBlocks(): self
    {
        Logger::info('Layout: Generating blocks');
        
        // Blocks are already created, just need to render
        return $this;
    }
    
    /**
     * Get block by name
     *
     * @param string $name
     * @return AbstractBlock|null
     */
    public function getBlock(string $name): ?AbstractBlock
    {
        return $this->blocks[$name] ?? null;
    }
    
    /**
     * Create block and add to layout
     *
     * @param string $name
     * @param string $class
     * @param string|null $template
     * @return AbstractBlock
     */
    public function createBlock(string $name, string $class, ?string $template = null): AbstractBlock
    {
        $block = $this->blockFactory->create($class);
        $block->setName($name);
        
        if ($template) {
            $block->setTemplate($template);
        }
        
        $this->blocks[$name] = $block;
        
        return $block;
    }
    
    /**
     * Get rendered output
     *
     * @return string
     */
    public function getOutput(): string
    {
        if ($this->output === null) {
            // Find root block and render
            $rootBlock = $this->getBlock('root');
            
            if ($rootBlock) {
                $this->output = $rootBlock->toHtml();
            } else {
                Logger::warning('Layout: No root block found');
                $this->output = '';
            }
        }
        
        return $this->output;
    }
    
    /**
     * Render layout (shortcut for loadLayout + generateBlocks + getOutput)
     *
     * @return string
     */
    public function render(): string
    {
        $this->loadLayout();
        $this->generateBlocks();
        
        return $this->getOutput();
    }
}
