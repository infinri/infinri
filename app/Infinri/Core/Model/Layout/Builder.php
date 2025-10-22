<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Block\AbstractBlock;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;
use Infinri\Core\Block\Template;
use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Model\View\TemplateResolver;
use Infinri\Core\Model\Module\ModuleManager;
use SimpleXMLElement;

/**
 * Layout Builder
 * 
 * Builds a tree of block objects from processed layout XML.
 */
class Builder
{
    /**
     * @var array<string, AbstractBlock> Named blocks for reference
     */
    private array $blocks = [];

    public function __construct(
        private readonly TemplateResolver $templateResolver,
    ) {
    }

    /**
     * Build block tree from processed layout XML
     *
     * @param SimpleXMLElement $layout Processed layout XML
     * @return AbstractBlock|null Root block
     */
    public function build(SimpleXMLElement $layout): ?AbstractBlock
    {
        $this->blocks = [];
        
        // Find root element (usually a container)
        // Both frontend and admin layouts extend base_default from Theme
        foreach ($layout->children() as $element) {
            if ($element->getName() === 'container' || $element->getName() === 'block') {
                $rootBlock = $this->buildElement($element);
                $this->setLayoutOnBlocks($rootBlock, $this);
                return $rootBlock;
            }
        }
        
        return null;
    }

    /**
     * Build a block from XML element
     *
     * @param SimpleXMLElement $element
     * @return AbstractBlock
     */
    private function buildElement(SimpleXMLElement $element): AbstractBlock
    {
        $type = $element->getName();
        $name = isset($element['name']) ? (string) $element['name'] : null;
        
        // Create block instance
        $block = $this->createBlock($element);
        
        // Set block name
        if ($name) {
            $block->setName($name);
            $this->blocks[$name] = $block;
        }
        
        // Set template on Template blocks
        if ($block instanceof Template && isset($element['template'])) {
            $template = (string) $element['template'];
            $block->setTemplate($template);
            
            \Infinri\Core\Helper\Logger::debug('Builder: Created Template block', [
                'name' => $name,
                'template' => $template,
                'class' => get_class($block)
            ]);
        }
        
        // Set block data from attributes
        $this->setBlockData($block, $element);
        
        // Process <arguments> children
        $this->processArguments($block, $element);
        
        // Build and add children
        foreach ($element->children() as $child) {
            if (in_array($child->getName(), ['container', 'block'])) {
                $childBlock = $this->buildElement($child);
                $block->addChild($childBlock);
            }
        }
        
        return $block;
    }

    /**
     * Create block instance from XML element
     *
     * @param SimpleXMLElement $element
     * @return AbstractBlock
     */
    private function createBlock(SimpleXMLElement $element): AbstractBlock
    {
        $type = $element->getName();
        
        // Determine block class
        if ($type === 'container') {
            return new Container();
        }
        
        // For <block> elements, check if class is specified
        if (isset($element['class'])) {
            $className = (string) $element['class'];
            
            // Try to create via ObjectManager first (if available)
            try {
                $objectManager = ObjectManager::getInstance();
                if ($objectManager->has($className)) {
                    $block = $objectManager->create($className);
                    if ($block instanceof AbstractBlock) {
                        return $block;
                    }
                }
            } catch (\RuntimeException $e) {
                // ObjectManager not configured yet (likely in tests)
                // Fall through to regular instantiation
            }

            // Try direct instantiation
            if (class_exists($className)) {
                $block = new $className();
                if ($block instanceof AbstractBlock) {
                    // Inject TemplateResolver for Template blocks
                    if ($block instanceof Template && $this->templateResolver !== null) {
                        $block->setTemplateResolver($this->templateResolver);
                    }
                    return $block;
                }
            }
        }
        
        // Default to Text block
        return new Text();
    }

    /**
     * Set block data from XML attributes
     *
     * @param AbstractBlock $block
     * @param SimpleXMLElement $element
     * @return void
     */
    private function setBlockData(AbstractBlock $block, SimpleXMLElement $element): void
    {
        foreach ($element->attributes() as $key => $value) {
            if ($key !== 'class' && $key !== 'name' && $key !== 'template') {
                $block->setData((string) $key, (string) $value);
            }
        }
    }

    /**
     * Process block arguments from XML
     *
     * @param AbstractBlock $block
     * @param SimpleXMLElement $element
     * @return void
     */
    private function processArguments(AbstractBlock $block, SimpleXMLElement $element): void
    {
        // Find <arguments> child
        $argumentsNodes = $element->xpath('arguments');
        
        if (empty($argumentsNodes)) {
            return;
        }
        
        foreach ($argumentsNodes as $argumentsNode) {
            // Process each <argument> within <arguments>
            foreach ($argumentsNode->children() as $argument) {
                if ($argument->getName() === 'argument') {
                    $name = isset($argument['name']) ? (string) $argument['name'] : null;
                    if (!$name) {
                        continue;
                    }
                    
                    // Get the argument value (text content)
                    $value = trim((string) $argument);
                    
                    // Check for xsi:type attribute
                    $namespaces = $argument->getNameSpaces(true);
                    $xsiType = null;
                    
                    if (isset($namespaces['xsi'])) {
                        $xsiAttrs = $argument->attributes('xsi', true);
                        $xsiType = isset($xsiAttrs['type']) ? (string) $xsiAttrs['type'] : null;
                    }
                    
                    \Infinri\Core\Helper\Logger::info('Builder: Processing argument', [
                        'name' => $name,
                        'value' => substr($value, 0, 100),
                        'xsiType' => $xsiType,
                        'block_name' => $block->getName()
                    ]);
                    
                    // Handle different types
                    if ($xsiType === 'object') {
                        try {
                            $objectManager = ObjectManager::getInstance();
                            // Instantiate the object using ObjectManager
                            $value = $objectManager->get($value);
                            \Infinri\Core\Helper\Logger::info('Builder: Instantiated ViewModel', [
                                'name' => $name,
                                'class' => get_class($value)
                            ]);
                        } catch (\RuntimeException $e) {
                            // ObjectManager not configured, skip ViewModel
                            \Infinri\Core\Helper\Logger::debug('Builder: ObjectManager not available for ViewModel', [
                                'name' => $name,
                                'value' => $value
                            ]);
                            continue;
                        }

                    } elseif ($xsiType === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($xsiType === 'number' || $xsiType === 'int') {
                        $value = (int) $value;
                    }
                    
                    $block->setData($name, $value);
                }
            }
        }
    }

    /**
     * Recursively set layout reference on Template blocks
     *
     * @param AbstractBlock $block
     * @param object $layout
     * @return void
     */
    private function setLayoutOnBlocks(AbstractBlock $block, object $layout): void
    {
        if ($block instanceof Template) {
            $block->setLayout($layout);
        }
        
        if ($block instanceof Container) {
            foreach ($block->getChildren() as $child) {
                $this->setLayoutOnBlocks($child, $layout);
            }
        }
    }

    /**
     * Get named block
     *
     * @param string $name
     * @return AbstractBlock|null
     */
    public function getBlock(string $name): ?AbstractBlock
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * Get all blocks
     *
     * @return array<string, AbstractBlock>
     */
    public function getAllBlocks(): array
    {
        return $this->blocks;
    }
}
