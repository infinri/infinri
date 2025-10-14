<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Block\AbstractBlock;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;
use Infinri\Core\Model\ObjectManager;
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
        private readonly ?ObjectManager $objectManager = null
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
        foreach ($layout->children() as $element) {
            if ($element->getName() === 'container' || $element->getName() === 'block') {
                return $this->buildElement($element);
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
        
        // Set block data from attributes
        $this->setBlockData($block, $element);
        
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
            if ($this->objectManager !== null) {
                try {
                    if ($this->objectManager->has($className)) {
                        $block = $this->objectManager->create($className);
                        if ($block instanceof AbstractBlock) {
                            return $block;
                        }
                    }
                } catch (\Exception $e) {
                    // Fall through to default
                }
            }
            
            // Try direct instantiation
            if (class_exists($className)) {
                $block = new $className();
                if ($block instanceof AbstractBlock) {
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
            if ($key !== 'class' && $key !== 'name') {
                $block->setData((string) $key, (string) $value);
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
