<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use SimpleXMLElement;

/**
 * Layout Processor
 * 
 * Processes layout XML directives (block, container, referenceBlock, move, remove, etc.)
 */
class Processor
{
    /**
     * @var array<string, SimpleXMLElement> Named elements (blocks/containers) for reference
     */
    private array $namedElements = [];

    /**
     * Process layout XML and return structure ready for building
     *
     * @param SimpleXMLElement $layout
     * @return SimpleXMLElement Processed layout
     */
    public function process(SimpleXMLElement $layout): SimpleXMLElement
    {
        $this->namedElements = [];
        
        // First pass: collect all named elements
        $this->collectNamedElements($layout);
        
        // Second pass: process directives
        $this->processDirectives($layout);
        
        return $layout;
    }

    /**
     * Collect all elements with 'name' attribute for later reference
     *
     * @param SimpleXMLElement $element
     * @return void
     */
    private function collectNamedElements(SimpleXMLElement $element): void
    {
        if (isset($element['name'])) {
            $name = (string) $element['name'];
            $this->namedElements[$name] = $element;
        }
        
        foreach ($element->children() as $child) {
            $this->collectNamedElements($child);
        }
    }

    /**
     * Process all layout directives
     *
     * @param SimpleXMLElement $layout
     * @return void
     */
    private function processDirectives(SimpleXMLElement $layout): void
    {
        // Process <remove> directives
        $this->processRemoveDirectives($layout);
        
        // Process <move> directives
        $this->processMoveDirectives($layout);
        
        // Process <referenceBlock> and <referenceContainer>
        $this->processReferenceDirectives($layout);
    }

    /**
     * Process <remove> directives
     *
     * @param SimpleXMLElement $layout
     * @return void
     */
    private function processRemoveDirectives(SimpleXMLElement $layout): void
    {
        // Get all remove directives
        while ($removes = $layout->xpath('//remove[@name]')) {
            if (empty($removes)) {
                break;
            }
            
            foreach ($removes as $remove) {
                $name = (string) $remove['name'];
                
                // Find and remove the named element
                if ($namedElement = $layout->xpath("//*[@name='" . $name . "']")) {
                    foreach ($namedElement as $element) {
                        if ($element->getName() !== 'remove') {
                            $dom = dom_import_simplexml($element);
                            if ($dom && $dom->parentNode) {
                                $dom->parentNode->removeChild($dom);
                            }
                        }
                    }
                }
                
                // Remove the <remove> directive itself
                $dom = dom_import_simplexml($remove);
                if ($dom && $dom->parentNode) {
                    $dom->parentNode->removeChild($dom);
                }
            }
        }
        
        // Refresh named elements after removals
        $this->namedElements = [];
        $this->collectNamedElements($layout);
    }

    /**
     * Process <move> directives
     *
     * @param SimpleXMLElement $layout
     * @return void
     */
    private function processMoveDirectives(SimpleXMLElement $layout): void
    {
        $moves = $layout->xpath('//move[@element and @destination]');
        
        foreach ($moves as $move) {
            $elementName = (string) $move['element'];
            $destination = (string) $move['destination'];
            $before = isset($move['before']) ? (string) $move['before'] : null;
            $after = isset($move['after']) ? (string) $move['after'] : null;
            
            if (isset($this->namedElements[$elementName]) && isset($this->namedElements[$destination])) {
                $element = $this->namedElements[$elementName];
                $dest = $this->namedElements[$destination];
                
                // Move element to destination
                $this->moveElement($element, $dest, $before, $after);
            }
            
            // Remove the <move> directive
            $dom = dom_import_simplexml($move);
            if ($dom && $dom->parentNode) {
                $dom->parentNode->removeChild($dom);
            }
        }
    }

    /**
     * Move an element to a new destination
     *
     * @param SimpleXMLElement $element
     * @param SimpleXMLElement $destination
     * @param string|null $before
     * @param string|null $after
     * @return void
     */
    private function moveElement(
        SimpleXMLElement $element,
        SimpleXMLElement $destination,
        ?string $before,
        ?string $after
    ): void {
        // Convert to DOM for manipulation
        $sourceDom = dom_import_simplexml($element);
        $destDom = dom_import_simplexml($destination);
        
        if (!$sourceDom || !$destDom || !$sourceDom->parentNode) {
            return;
        }
        
        // Import node into destination document
        $importedNode = $destDom->ownerDocument->importNode($sourceDom, true);
        
        // Remove from original parent
        $sourceDom->parentNode->removeChild($sourceDom);
        
        // Handle positioning with before/after
        if ($before !== null) {
            // Insert before specific sibling
            $siblings = $destDom->childNodes;
            foreach ($siblings as $sibling) {
                if ($sibling->nodeType === XML_ELEMENT_NODE && 
                    $sibling->hasAttribute('name') && 
                    $sibling->getAttribute('name') === $before) {
                    $destDom->insertBefore($importedNode, $sibling);
                    return;
                }
            }
            // If before element not found, append to end
            $destDom->appendChild($importedNode);
        } elseif ($after !== null) {
            // Insert after specific sibling
            $siblings = $destDom->childNodes;
            $found = false;
            foreach ($siblings as $sibling) {
                if ($found) {
                    // Insert before the next sibling (which is after our target)
                    $destDom->insertBefore($importedNode, $sibling);
                    return;
                }
                if ($sibling->nodeType === XML_ELEMENT_NODE && 
                    $sibling->hasAttribute('name') && 
                    $sibling->getAttribute('name') === $after) {
                    $found = true;
                }
            }
            // If we found the after element and it's the last one, or didn't find it, append
            $destDom->appendChild($importedNode);
        } else {
            // No positioning specified, append to end
            $destDom->appendChild($importedNode);
        }
    }

    /**
     * Process <referenceBlock> and <referenceContainer> directives
     *
     * @param SimpleXMLElement $layout
     * @return void
     */
    private function processReferenceDirectives(SimpleXMLElement $layout): void
    {
        // Process until no more references found
        while ($references = $layout->xpath('//referenceBlock[@name] | //referenceContainer[@name]')) {
            if (empty($references)) {
                break;
            }
            
            foreach ($references as $reference) {
                $name = (string) $reference['name'];
                
                // Find the target element by name
                $targets = $layout->xpath("//*[@name='" . $name . "']");
                
                if (!empty($targets)) {
                    foreach ($targets as $target) {
                        // Skip if target is a reference directive itself
                        if ($target->getName() === 'referenceBlock' || $target->getName() === 'referenceContainer') {
                            continue;
                        }
                        
                        // Merge children from reference into target
                        foreach ($reference->children() as $child) {
                            $this->appendElement($target, $child);
                        }
                        break; // Only process first matching target
                    }
                }
                
                // Remove the reference directive
                $dom = dom_import_simplexml($reference);
                if ($dom && $dom->parentNode) {
                    $dom->parentNode->removeChild($dom);
                }
            }
        }
        
        // Refresh named elements after processing
        $this->namedElements = [];
        $this->collectNamedElements($layout);
    }

    /**
     * Append element to target
     *
     * @param SimpleXMLElement $target
     * @param SimpleXMLElement $source
     * @return void
     */
    private function appendElement(SimpleXMLElement $target, SimpleXMLElement $source): void
    {
        // Use DOM to properly clone and import the element
        $targetDom = dom_import_simplexml($target);
        $sourceDom = dom_import_simplexml($source);
        
        if (!$targetDom || !$sourceDom) {
            return;
        }
        
        // Import the source node (deep copy with all children)
        $importedNode = $targetDom->ownerDocument->importNode($sourceDom, true);
        
        // Append to target
        $targetDom->appendChild($importedNode);
    }

    /**
     * Get all named elements
     *
     * @return array<string, SimpleXMLElement>
     */
    public function getNamedElements(): array
    {
        return $this->namedElements;
    }
}
