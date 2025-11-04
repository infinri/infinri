<?php

declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * A simple block that outputs text content.
 */
class Text extends AbstractBlock
{
    /**
     * @var string Text content
     */
    private string $text = '';

    /**
     * Set text content.
     *
     * @return $this
     */
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text content.
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function toHtml(): string
    {
        // Check data array first (set from XML arguments)
        $dataText = $this->getData('text');
        if (null !== $dataText) {
            return $dataText;
        }

        // Fall back to text property
        return $this->text;
    }
}
