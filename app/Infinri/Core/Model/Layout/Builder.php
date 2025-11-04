<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Block\AbstractBlock;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Template;
use Infinri\Core\Block\Text;
use Infinri\Core\Model\ObjectManager;
use Infinri\Core\Model\View\TemplateResolver;

/**
 * Builds a tree of block objects from processed layout XML.
 */
class Builder
{
    /**
     * @var array<string, AbstractBlock> Named blocks for reference
     */
    private array $blocks = [];

    /**
     * @var array Layout data (page, category, product, etc.)
     */
    private array $data = [];

    public function __construct(
        private readonly TemplateResolver $templateResolver,
    ) {
    }

    /**
     * Build block tree from processed layout XML.
     *
     * @param \SimpleXMLElement    $layout Processed layout XML
     * @param array<string, mixed> $data   Layout data to pass to blocks
     *
     * @return AbstractBlock|null Root block
     */
    public function build(\SimpleXMLElement $layout, array $data = []): ?AbstractBlock
    {
        $this->blocks = [];
        $this->data = $data;

        // Find root element (usually a container)
        // Both frontend and admin layouts extend base_default from Theme
        foreach ($layout->children() as $element) {
            if ('container' === $element->getName() || 'block' === $element->getName()) {
                $rootBlock = $this->buildElement($element);
                $this->setLayoutOnBlocks($rootBlock, $this);

                return $rootBlock;
            }
        }

        return null;
    }

    /**
     * Build a block from XML element.
     */
    private function buildElement(\SimpleXMLElement $element): AbstractBlock
    {
        $type = $element->getName();
        $name = isset($element['name']) ? (string) $element['name'] : null;

        if ('admin.sidebar' === $name || 'admin.navigation' === $name) {
            \Infinri\Core\Helper\Logger::info('Builder: Creating sidebar element', [
                'name' => $name,
                'type' => $type,
                'class' => isset($element['class']) ? (string) $element['class'] : 'N/A',
            ]);
        }

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
                'class' => $block::class,
            ]);
        }

        // Set block data from attributes
        $this->setBlockData($block, $element);

        // Process <arguments> children
        $this->processArguments($block, $element);

        // Build and add children
        foreach ($element->children() as $child) {
            if (\in_array($child->getName(), ['container', 'block'], true)) {
                $childBlock = $this->buildElement($child);
                $block->addChild($childBlock);
            }
        }

        return $block;
    }

    /**
     * Create block instance from XML element.
     */
    private function createBlock(\SimpleXMLElement $element): AbstractBlock
    {
        $type = $element->getName();

        // Determine block class
        if ('container' === $type) {
            return new Container();
        }

        // For <block> elements, check if class is specified
        if (isset($element['class'])) {
            $className = (string) $element['class'];

            // Try to create via ObjectManager (handles dependency injection)
            try {
                $objectManager = ObjectManager::getInstance();
                /** @var AbstractBlock|object $block */
                $block = $objectManager->create($className); // @phpstan-ignore-line

                if ($block instanceof AbstractBlock) {
                    // Inject TemplateResolver for Template blocks
                    if ($block instanceof Template) {
                        $block->setTemplateResolver($this->templateResolver);
                    }

                    return $block;
                }
            } catch (\RuntimeException $e) {
                // ObjectManager not configured yet (likely in tests)
                // Fall through to direct instantiation for simple blocks
            } catch (\Throwable $e) {
                // Failed to create via ObjectManager - log and try fallback
                \Infinri\Core\Helper\Logger::warning('Builder: Failed to create block via ObjectManager', [
                    'class' => $className,
                    'error' => $e->getMessage(),
                ]);
            }

            // Fallback: Try direct instantiation (only works for blocks without dependencies)
            if (class_exists($className)) {
                try {
                    $block = new $className(); // @phpstan-ignore-line
                    if ($block instanceof AbstractBlock) {
                        // Inject TemplateResolver for Template blocks
                        if ($block instanceof Template) {
                            $block->setTemplateResolver($this->templateResolver);
                        }

                        return $block;
                    }
                } catch (\Throwable $e) {
                    // Constructor requires dependencies - cannot instantiate
                    \Infinri\Core\Helper\Logger::error('Builder: Failed to create block', [
                        'class' => $className,
                        'error' => $e->getMessage(),
                        'hint' => 'Block requires dependencies but ObjectManager is not available or class is not registered',
                    ]);
                }
            }
        }

        // Default to Text block
        return new Text();
    }

    /**
     * Set block data from XML attributes.
     */
    private function setBlockData(AbstractBlock $block, \SimpleXMLElement $element): void
    {
        foreach ($element->attributes() as $key => $value) {
            /** @var string $key */
            if ('class' !== $key && 'name' !== $key && 'template' !== $key) {
                $block->setData($key, (string) $value);
            }
        }
    }

    /**
     * Process block arguments from XML.
     *
     * @throws \Throwable
     */
    private function processArguments(AbstractBlock $block, \SimpleXMLElement $element): void
    {
        // Find <arguments> child
        $argumentsNodes = $element->xpath('arguments');

        if (empty($argumentsNodes)) {
            return;
        }

        foreach ($argumentsNodes as $argumentsNode) {
            // Process each <argument> within <arguments>
            foreach ($argumentsNode->children() as $argument) {
                if ('argument' === $argument->getName()) {
                    $name = isset($argument['name']) ? (string) $argument['name'] : null;
                    if (! $name) {
                        continue;
                    }

                    // Get the argument value (text content)
                    $value = trim((string) $argument);

                    // Check for xsi:type attribute - try multiple methods
                    $xsiType = null;

                    // Method 1: Try with xsi namespace
                    $namespaces = $argument->getNamespaces(true);
                    if (isset($namespaces['xsi'])) {
                        $xsiAttrs = $argument->attributes('xsi', true);
                        $xsiType = isset($xsiAttrs['type']) ? (string) $xsiAttrs['type'] : null;
                    }

                    // Method 2: Try with http://www.w3.org/2001/XMLSchema-instance namespace directly
                    if (! $xsiType) {
                        $xsiAttrs = $argument->attributes('http://www.w3.org/2001/XMLSchema-instance', true);
                        $xsiType = isset($xsiAttrs['type']) ? (string) $xsiAttrs['type'] : null;
                    }

                    // Method 3: Try as regular attribute (namespace might be stripped)
                    if (! $xsiType && isset($argument['type'])) {
                        $xsiType = (string) $argument['type'];
                    }

                    \Infinri\Core\Helper\Logger::info('Builder: Processing argument', [
                        'name' => $name,
                        'value' => substr($value, 0, 100),
                        'xsiType' => $xsiType,
                        'block_name' => $block->getName(),
                    ]);

                    // Handle different types
                    if ('object' === $xsiType) {
                        // Check if value is a data key reference (no namespace separator) or a class name
                        if (! str_contains($value, '\\') && isset($this->data[$value])) {
                            // Reference to layout data - use the value from data array
                            $dataKey = $value;
                            $value = $this->data[$value];
                            \Infinri\Core\Helper\Logger::info('Builder: Resolved data reference', [
                                'name' => $name,
                                'data_key' => $dataKey,
                                'resolved_type' => \is_object($value) ? $value::class : \gettype($value),
                            ]);
                        } else {
                            // Class name - instantiate via ObjectManager
                            try {
                                $objectManager = ObjectManager::getInstance();
                                $value = $objectManager->get($value); // @phpstan-ignore-line
                                \Infinri\Core\Helper\Logger::info('Builder: Instantiated ViewModel', [
                                    'name' => $name,
                                    'class' => $value::class,
                                ]);
                            } catch (\RuntimeException $e) {
                                // ObjectManager not configured, skip ViewModel
                                \Infinri\Core\Helper\Logger::debug('Builder: ObjectManager not available for ViewModel', [
                                    'name' => $name,
                                    'value' => $value,
                                ]);
                                continue;
                            }
                        }
                    } elseif ('boolean' === $xsiType) {
                        $value = filter_var($value, \FILTER_VALIDATE_BOOLEAN);
                    } elseif ('number' === $xsiType || 'int' === $xsiType) {
                        $value = (int) $value;
                    }

                    // Special handling for 'template' argument on Template blocks
                    if ('template' === $name && $block instanceof Template) {
                        $block->setTemplate($value);
                        \Infinri\Core\Helper\Logger::debug('Builder: Set template via argument', [
                            'block_name' => $block->getName(),
                            'template' => $value,
                        ]);
                    } else {
                        $block->setData($name, $value);
                    }
                }
            }
        }
    }

    /**
     * Recursively set layout reference on Template blocks.
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
     * Get named block.
     */
    public function getBlock(string $name): ?AbstractBlock
    {
        return $this->blocks[$name] ?? null;
    }

    /**
     * Get all blocks.
     *
     * @return array<string, AbstractBlock>
     */
    public function getAllBlocks(): array
    {
        return $this->blocks;
    }
}
