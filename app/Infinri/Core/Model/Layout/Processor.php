<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

/**
 * Processes layout XML directives (block, container, referenceBlock, move, remove, etc.).
 */
class Processor
{
    /**
     * @var array<string, \SimpleXMLElement> Named elements (blocks/containers) for reference
     */
    private array $namedElements = [];

    /**
     * Process layout XML and return structure ready for building.
     *
     * @return \SimpleXMLElement Processed layout
     */
    public function process(\SimpleXMLElement $layout): \SimpleXMLElement
    {
        $this->namedElements = [];

        // First pass: collect all named elements
        $this->collectNamedElements($layout);

        // Second pass: process directives
        $this->processDirectives($layout);

        return $layout;
    }

    /**
     * Collect all elements with 'name' attribute for later reference.
     */
    private function collectNamedElements(\SimpleXMLElement $element): void
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
     * Process all layout directives.
     */
    private function processDirectives(\SimpleXMLElement $layout): void
    {
        // Process <remove> directives
        $this->processRemoveDirectives($layout);

        // Process <move> directives
        $this->processMoveDirectives($layout);

        // Process <referenceBlock> and <referenceContainer>
        $this->processReferenceDirectives($layout);
    }

    /**
     * Process <remove> directives.
     */
    private function processRemoveDirectives(\SimpleXMLElement $layout): void
    {
        // Get all remove directives
        $removes = $layout->xpath('//remove[@name]');
        if (! \is_array($removes)) {
            return;
        }

        while (! empty($removes)) {
            foreach ($removes as $remove) {
                $name = (string) $remove['name'];

                // Find and remove the named element
                $namedElement = $layout->xpath("//*[@name='" . $name . "']");
                if (\is_array($namedElement)) {
                    foreach ($namedElement as $element) {
                        if ('remove' !== $element->getName()) {
                            $dom = dom_import_simplexml($element);
                            if ($dom instanceof \DOMElement && $dom->parentNode) {
                                $dom->parentNode->removeChild($dom);
                            }
                        }
                    }
                }

                // Remove the <remove> directive itself
                $dom = dom_import_simplexml($remove);
                if ($dom instanceof \DOMElement && $dom->parentNode) {
                    $dom->parentNode->removeChild($dom);
                }
            }

            // Re-fetch removes for next iteration
            $removes = $layout->xpath('//remove[@name]');
            if (! \is_array($removes) || empty($removes)) {
                break;
            }
        }

        // Refresh named elements after removals
        $this->namedElements = [];
        $this->collectNamedElements($layout);
    }

    /**
     * Process <move> directives.
     */
    private function processMoveDirectives(\SimpleXMLElement $layout): void
    {
        $moves = $layout->xpath('//move[@element and @destination]');
        if (! \is_array($moves)) {
            return;
        }

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
            if ($dom instanceof \DOMElement && $dom->parentNode) {
                $dom->parentNode->removeChild($dom);
            }
        }
    }

    /**
     * Move an element to a new destination.
     */
    private function moveElement(
        \SimpleXMLElement $element,
        \SimpleXMLElement $destination,
        ?string $before,
        ?string $after
    ): void {
        // Convert to DOM for manipulation
        $sourceDom = dom_import_simplexml($element);
        $destDom = dom_import_simplexml($destination);

        if (! ($sourceDom instanceof \DOMElement) || ! ($destDom instanceof \DOMElement) || ! $sourceDom->parentNode) {
            return;
        }

        // Import node into destination document
        $importedNode = $destDom->ownerDocument->importNode($sourceDom, true);

        // Remove from original parent
        $sourceDom->parentNode->removeChild($sourceDom);

        // Handle positioning with before/after
        if (null !== $before) {
            // Insert before specific sibling
            $siblings = $destDom->childNodes;
            foreach ($siblings as $sibling) {
                if (
                    \XML_ELEMENT_NODE === $sibling->nodeType
                    && $sibling->hasAttribute('name')
                    && $sibling->getAttribute('name') === $before
                ) {
                    $destDom->insertBefore($importedNode, $sibling);

                    return;
                }
            }
            // If before element not found, append to end
            $destDom->appendChild($importedNode);
        } elseif (null !== $after) {
            // Insert after specific sibling
            $siblings = $destDom->childNodes;
            $found = false;
            foreach ($siblings as $sibling) {
                if ($found) {
                    // Insert before the next sibling (which is after our target)
                    $destDom->insertBefore($importedNode, $sibling);

                    return;
                }
                if (
                    \XML_ELEMENT_NODE === $sibling->nodeType
                    && $sibling->hasAttribute('name')
                    && $sibling->getAttribute('name') === $after
                ) {
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
     * Process <referenceBlock> and <referenceContainer> directives.
     */
    private function processReferenceDirectives(\SimpleXMLElement $layout): void
    {
        // Process until no more references found
        $references = $layout->xpath('//referenceBlock[@name] | //referenceContainer[@name]');
        if (! \is_array($references)) {
            return;
        }

        while (! empty($references)) {
            foreach ($references as $reference) {
                $name = (string) $reference['name'];

                // Find the target element by name
                $targets = $layout->xpath("//*[@name='" . $name . "']");

                if (! empty($targets)) {
                    $targetProcessed = false;
                    foreach ($targets as $target) {
                        // Skip if target is a reference directive itself
                        if ('referenceBlock' === $target->getName() || 'referenceContainer' === $target->getName()) {
                            continue;
                        }

                        $targetProcessed = true;

                        // This prevents duplicate keys when multiple children have the same tag name
                        $children = iterator_to_array($reference->children(), false);

                        foreach ($children as $child) {
                            try {
                                $this->appendElement($target, $child);
                            } catch (\Throwable $e) {
                                $childName = isset($child['name']) ? (string) $child['name'] : $child->getName();
                                \Infinri\Core\Helper\Logger::error('Processor: Failed to append child', [
                                    'child_name' => $childName,
                                    'parent' => $name,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        break; // Only process first matching target
                    }
                }

                // Remove the reference directive
                $dom = dom_import_simplexml($reference);
                if ($dom instanceof \DOMElement && $dom->parentNode) {
                    $dom->parentNode->removeChild($dom);
                }
            }

            // Re-fetch references for next iteration
            $references = $layout->xpath('//referenceBlock[@name] | //referenceContainer[@name]');
            if (! \is_array($references) || empty($references)) {
                break;
            }
        }

        // Refresh named elements after processing
        $this->namedElements = [];
        $this->collectNamedElements($layout);
    }

    /**
     * Append element to target.
     */
    private function appendElement(\SimpleXMLElement $target, \SimpleXMLElement $source): void
    {
        // Use DOM to properly clone and import the element
        $targetDom = dom_import_simplexml($target);
        $sourceDom = dom_import_simplexml($source);

        if (! ($targetDom instanceof \DOMElement) || ! ($sourceDom instanceof \DOMElement)) {
            return;
        }

        // Import the source node (deep copy with all children)
        $importedNode = $targetDom->ownerDocument->importNode($sourceDom, true);

        // Check for 'before' or 'after' attributes on the source
        $before = isset($source['before']) ? (string) $source['before'] : null;
        $after = isset($source['after']) ? (string) $source['after'] : null;

        $sourceName = isset($source['name']) ? (string) $source['name'] : 'unnamed';
        $targetName = isset($target['name']) ? (string) $target['name'] : 'unnamed';

        if ('admin.sidebar' === $sourceName || 'main.content' === $targetName) {
            \Infinri\Core\Helper\Logger::info('Processor: Appending element', [
                'source' => $sourceName,
                'target' => $targetName,
                'before' => $before,
                'after' => $after,
            ]);
        }

        if (null !== $before) {
            // Insert before specific sibling
            $siblings = $targetDom->childNodes;
            $found = false;
            foreach ($siblings as $sibling) {
                if (
                    \XML_ELEMENT_NODE === $sibling->nodeType
                    && $sibling->hasAttribute('name')
                    && $sibling->getAttribute('name') === $before
                ) {
                    $targetDom->insertBefore($importedNode, $sibling);
                    $found = true;

                    if ('admin.sidebar' === $sourceName) {
                        \Infinri\Core\Helper\Logger::info('Processor: Inserted sidebar BEFORE content', [
                            'before' => $before,
                            'found' => true,
                        ]);
                    }

                    return;
                }
            }
            // If before element not found, append to end
            if ('admin.sidebar' === $sourceName) {
                \Infinri\Core\Helper\Logger::warning('Processor: Could not find before element, appending sidebar to end', [
                    'before' => $before,
                    'found' => $found,
                    'sibling_count' => $siblings->length,
                ]);
            }
            $targetDom->appendChild($importedNode);
        } elseif (null !== $after) {
            // Insert after specific sibling
            $siblings = $targetDom->childNodes;
            $found = false;
            foreach ($siblings as $sibling) {
                if ($found) {
                    $targetDom->insertBefore($importedNode, $sibling);

                    return;
                }
                if (
                    \XML_ELEMENT_NODE === $sibling->nodeType
                    && $sibling->hasAttribute('name')
                    && $sibling->getAttribute('name') === $after
                ) {
                    $found = true;
                }
            }
            // If found and it's the last one, or not found, append
            $targetDom->appendChild($importedNode);
        } else {
            // No positioning specified, append to end
            $targetDom->appendChild($importedNode);
        }
    }

    /**
     * Get all named elements.
     *
     * @return array<string, \SimpleXMLElement>
     */
    public function getNamedElements(): array
    {
        return $this->namedElements;
    }
}
