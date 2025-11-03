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
     * Set text content
     *
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Get text content
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        // Check data array first (set from XML arguments)
        $dataText = $this->getData('text');
        if ($dataText !== null) {
            return $dataText;
        }

        // Fall back to text property
        return $this->text;
    }
}
