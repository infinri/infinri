<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * A block that can contain child blocks and render them within an HTML tag.
 */
class Container extends AbstractBlock
{
    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $htmlTag = $this->getData('htmlTag');
        $htmlClass = $this->getData('htmlClass') ?: '';
        $htmlId = $this->getData('htmlId') ?: '';

        // Get children HTML first
        $childrenHtml = $this->getChildrenHtml();

        // If no HTML tag specified, just return children without wrapper
        if (empty($htmlTag)) {
            return $childrenHtml;
        }

        // Build opening tag attributes
        $attributes = [];

        if ($htmlId) {
            $attributes[] = sprintf('id="%s"', htmlspecialchars($htmlId, ENT_QUOTES, 'UTF-8'));
        }

        if ($htmlClass) {
            $attributes[] = sprintf('class="%s"', htmlspecialchars($htmlClass, ENT_QUOTES, 'UTF-8'));
        }

        $attributeString = $attributes ? ' ' . implode(' ', $attributes) : '';

        // Self-closing tags
        if (in_array($htmlTag, ['br', 'hr', 'img', 'input', 'meta', 'link'], true)) {
            return sprintf('<%s%s />', $htmlTag, $attributeString);
        }

        // Build complete HTML with wrapper tag
        return sprintf(
            '<%s%s>%s</%s>',
            $htmlTag,
            $attributeString,
            $childrenHtml,
            $htmlTag
        );
    }

}
