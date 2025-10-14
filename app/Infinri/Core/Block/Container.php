<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * Container Block
 * 
 * A block that can contain child blocks and render them within an HTML tag.
 */
class Container extends AbstractBlock
{
    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $htmlTag = $this->getData('htmlTag') ?: 'div';
        $htmlClass = $this->getData('htmlClass') ?: '';
        $htmlId = $this->getData('htmlId') ?: '';
        
        // Build opening tag
        $attributes = [];
        
        if ($htmlId) {
            $attributes[] = sprintf('id="%s"', htmlspecialchars($htmlId));
        }
        
        if ($htmlClass) {
            $attributes[] = sprintf('class="%s"', htmlspecialchars($htmlClass));
        }
        
        $attributeString = $attributes ? ' ' . implode(' ', $attributes) : '';
        
        // Self-closing tags
        if (in_array($htmlTag, ['br', 'hr', 'img', 'input', 'meta', 'link'])) {
            return sprintf('<%s%s />', $htmlTag, $attributeString);
        }
        
        // Get children HTML
        $childrenHtml = $this->getChildrenHtml();
        
        // Build complete HTML
        return sprintf(
            '<%s%s>%s</%s>',
            $htmlTag,
            $attributeString,
            $childrenHtml,
            $htmlTag
        );
    }
}
