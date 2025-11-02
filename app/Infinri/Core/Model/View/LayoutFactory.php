<?php

declare(strict_types=1);

namespace Infinri\Core\Model\View;

use Infinri\Core\Model\Layout\Loader;
use Infinri\Core\Model\Layout\Merger;
use Infinri\Core\Model\Layout\Processor;
use Infinri\Core\Model\Layout\Builder;
use Infinri\Core\Model\Layout\Renderer;
use Infinri\Core\Block\AbstractBlock;
use Infinri\Core\Block\Container;
use Infinri\Core\Helper\Logger;

/**
 * Layout Factory
 * 
 * Creates and configures Layout rendering pipeline
 * Controller helper for rendering pages with layout XML
 */
class LayoutFactory
{
    public function __construct(
        private readonly Loader $loader,
        private readonly Merger $merger,
        private readonly Processor $processor,
        private readonly Builder $builder,
        private readonly Renderer $renderer
    ) {
    }
    
    /**
     * Render layout for given handle(s) with optional data
     *
     * @param string|array $handles Layout handle(s)
     * @param array $data Data to pass to blocks
     * @return string Rendered HTML
     */
    public function render(string|array $handles, array $data = []): string
    {
        $handles = is_array($handles) ? $handles : [$handles];
        
        Logger::info('LayoutFactory: Rendering layout', [
            'handles' => $handles,
            'data_keys' => array_keys($data)
        ]);
        
        try {
            // Load layout XML files for all handles (including referenced ones)
            $layoutXmlFiles = $this->loadHandlesRecursively($handles);
            
            if (empty($layoutXmlFiles)) {
                Logger::warning('LayoutFactory: No layout XML found', ['handles' => $handles]);
                return '';
            }
            
            Logger::debug('LayoutFactory: Loaded XML files', [
                'count' => count($layoutXmlFiles),
                'handles' => $handles
            ]);
            
            // Merge all layout files
            $mergedXml = $this->merger->merge($layoutXmlFiles);
            
            $fullXml = $mergedXml->asXML();
            Logger::debug('LayoutFactory: Merged XML', [
                'xml_preview' => substr($fullXml, 0, 500)
            ]);
            
            // Check if sidebar is in merged XML
            if (strpos($fullXml, 'admin.sidebar') !== false) {
                Logger::info('LayoutFactory: SIDEBAR FOUND in merged XML');
            } else {
                Logger::warning('LayoutFactory: SIDEBAR NOT FOUND in merged XML');
            }
            
            // Process layout directives (references, removes, etc.)
            $processedXml = $this->processor->process($mergedXml);
            
            // Check if our CMS content block is in the processed XML
            $cmsContentBlocks = $processedXml->xpath('//block[@name="cms.page.content"]');
            Logger::debug('LayoutFactory: CMS content block check', [
                'found' => !empty($cmsContentBlocks),
                'count' => count($cmsContentBlocks)
            ]);
            
            Logger::debug('LayoutFactory: Processed XML', [
                'xml_preview' => substr($processedXml->asXML(), 0, 500),
                'full_xml_length' => strlen($processedXml->asXML())
            ]);
            
            // Build block tree with data
            $rootBlock = $this->builder->build($processedXml, $data);
            
            if (!$rootBlock) {
                Logger::warning('LayoutFactory: No root block created', [
                    'processed_xml' => $processedXml->asXML()
                ]);
                return '';
            }
            
            Logger::debug('LayoutFactory: Root block created', [
                'block_name' => $rootBlock->getName(),
                'block_class' => get_class($rootBlock)
            ]);
            
            // Set data on specific blocks (need to traverse the tree)
            $this->setBlockData($rootBlock, $data);
            
            // Find the block named "cms.page.content" and set the page data directly on that block
            $cmsContentBlock = $this->findBlockByName($rootBlock, 'cms.page.content');
            if ($cmsContentBlock) {
                foreach ($data as $key => $value) {
                    $cmsContentBlock->setData($key, $value);
                }
            }
            
            // Render
            $html = $this->renderer->render($rootBlock);
            
            Logger::info('LayoutFactory: Layout rendered successfully');
            
            return $html;
            
        } catch (\Exception $e) {
            Logger::exception($e, 'LayoutFactory: Error rendering layout');
            return '';
        }
    }
    
    /**
     * Load handles recursively, following <update handle="..."/> directives
     *
     * @param array $handles Initial handles to load
     * @param array $loaded Already loaded handles (to prevent infinite loops)
     * @return array Array of SimpleXMLElement objects
     */
    private function loadHandlesRecursively(array $handles, array &$loaded = []): array
    {
        $layoutXmlFiles = [];
        
        foreach ($handles as $handle) {
            // Skip if already loaded
            if (in_array($handle, $loaded, true)) {
                continue;
            }
            
            // Mark as loaded
            $loaded[] = $handle;
            
            // Load this handle's XML files
            $layoutsByModule = $this->loader->load($handle);
            
            if (empty($layoutsByModule)) {
                Logger::warning('LayoutFactory: No XML found for handle', ['handle' => $handle]);
                continue;
            }
            
            Logger::debug('LayoutFactory: Loaded modules for handle', [
                'handle' => $handle,
                'modules' => array_keys($layoutsByModule)
            ]);
            
            // Extract XML elements and check for <update> directives (preserve module names)
            $referencedHandles = [];
            
            foreach ($layoutsByModule as $moduleName => $xml) {
                // Use handle_module as key to prevent overwrites
                $layoutXmlFiles[$handle . '_' . $moduleName] = $xml;
                
                // Find all <update handle="..."/> directives
                foreach ($xml->xpath('//update[@handle]') as $updateNode) {
                    $referencedHandle = (string)$updateNode['handle'];
                    if ($referencedHandle && !in_array($referencedHandle, $loaded, true)) {
                        $referencedHandles[] = $referencedHandle;
                    }
                }
            }
            
            // Recursively load referenced handles (they should be loaded FIRST for proper inheritance)
            if (!empty($referencedHandles)) {
                Logger::debug('LayoutFactory: Found referenced handles', [
                    'current_handle' => $handle,
                    'referenced_handles' => $referencedHandles
                ]);
                $referencedXml = $this->loadHandlesRecursively($referencedHandles, $loaded);
                // Prepend referenced XML so base layouts come first
                $layoutXmlFiles = array_merge($referencedXml, $layoutXmlFiles);
            }
        }
        
        return $layoutXmlFiles;
    }
    
    /**
     * Set data on blocks in the tree
     *
     * @param AbstractBlock $block
     * @param array $data
     * @return void
     */
    private function setBlockData(AbstractBlock $block, array $data): void
    {
        // Set data on this block
        foreach ($data as $key => $value) {
            $block->setData($key, $value);
        }
        
        // Recursively set data on children (Containers have children)
        if ($block instanceof Container) {
            $children = $block->getChildren();
            foreach ($children as $child) {
                $this->setBlockData($child, $data);
            }
        }
    }
    
    /**
     * Find a block by name in the tree
     *
     * @param AbstractBlock $block
     * @param string $name
     * @return AbstractBlock|null
     */
    private function findBlockByName(AbstractBlock $block, string $name): ?AbstractBlock
    {
        if ($block->getName() === $name) {
            return $block;
        }
        
        if (method_exists($block, 'getChildren')) {
            foreach ($block->getChildren() as $child) {
                $foundBlock = $this->findBlockByName($child, $name);
                if ($foundBlock) {
                    return $foundBlock;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Create layout pipeline for manual block building
     *
     * @return array{loader: Loader, merger: Merger, processor: Processor, builder: Builder, renderer: Renderer}
     */
    public function getComponents(): array
    {
        return [
            'loader' => $this->loader,
            'merger' => $this->merger,
            'processor' => $this->processor,
            'builder' => $this->builder,
            'renderer' => $this->renderer,
        ];
    }
}
