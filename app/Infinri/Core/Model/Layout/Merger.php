<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use SimpleXMLElement;

/**
 * Layout Merger
 * 
 * Merges multiple layout XML files into a single structure.
 */
class Merger
{
    /**
     * Merge multiple layout XML elements
     *
     * @param array<string, SimpleXMLElement> $layouts Module name => XML
     * @return SimpleXMLElement Merged layout XML
     */
    public function merge(array $layouts): SimpleXMLElement
    {
        // Create base layout structure
        $merged = new SimpleXMLElement('<?xml version="1.0"?><layout/>');
        
        // Merge each layout file in order
        foreach ($layouts as $moduleName => $xml) {
            \Infinri\Core\Helper\Logger::debug('Merger: Merging layout from module', ['module' => $moduleName]);
            $this->mergeXml($merged, $xml);
        }
        
        return $merged;
    }

    /**
     * Merge source XML into target XML
     *
     * @param SimpleXMLElement $target Target XML to merge into
     * @param SimpleXMLElement $source Source XML to merge from
     * @return void
     */
    private function mergeXml(SimpleXMLElement $target, SimpleXMLElement $source): void
    {
        // Iterate through all child elements of source
        foreach ($source->children() as $child) {
            $this->appendElement($target, $child);
        }
    }

    /**
     * Append element from source to target
     *
     * @param SimpleXMLElement $target
     * @param SimpleXMLElement $source
     * @return void
     */
    private function appendElement(SimpleXMLElement $target, SimpleXMLElement $source): void
    {
        $name = $source->getName();
        
        $sourceName = isset($source['name']) ? (string)$source['name'] : null;
        if ($sourceName === 'admin.sidebar' || $sourceName === 'main.content') {
            $childNames = [];
            foreach ($source->children() as $child) {
                $childName = isset($child['name']) ? (string)$child['name'] : $child->getName();
                $childNames[] = $childName;
            }
            
            \Infinri\Core\Helper\Logger::info('Merger: Appending element', [
                'type' => $name,
                'name' => $sourceName,
                'has_children' => $source->count() > 0,
                'child_count' => $source->count(),
                'children' => $childNames
            ]);
        }
        
        // Create new child element
        $new = $target->addChild($name);
        
        // Copy attributes
        foreach ($source->attributes() as $attrName => $attrValue) {
            $new->addAttribute($attrName, (string) $attrValue);
        }
        
        // Copy text content if no children
        if ($source->count() === 0) {
            $text = trim((string) $source);
            if ($text !== '') {
                $new[0] = $text;
            }
        }
        
        // Recursively copy children
        foreach ($source->children() as $child) {
            $this->appendElement($new, $child);
        }
    }

    /**
     * Process <update> directives to include other layout handles
     *
     * @param SimpleXMLElement $layout
     * @param Loader $loader
     * @return SimpleXMLElement
     */
    public function processUpdates(SimpleXMLElement $layout, Loader $loader): SimpleXMLElement
    {
        $updates = $layout->xpath('//update[@handle]');
        
        if (empty($updates)) {
            return $layout;
        }
        
        foreach ($updates as $update) {
            $handle = (string) $update['handle'];
            
            if ($handle) {
                // Load the referenced handle
                $includedLayouts = $loader->load($handle);
                
                // Merge included layouts before the update directive
                foreach ($includedLayouts as $includedXml) {
                    $this->mergeXml($layout, $includedXml);
                }
            }
            
            // Remove the update directive
            unset($update[0]);
        }
        
        return $layout;
    }
}
