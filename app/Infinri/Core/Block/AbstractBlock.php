<?php

declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * Base class for all blocks in the layout system.
 */
abstract class AbstractBlock
{
    /**
     * @var string|null Block name
     */
    private ?string $name = null;

    /**
     * @var array<string, AbstractBlock> Child blocks
     */
    private array $children = [];

    /**
     * @var AbstractBlock|null Parent block
     */
    private ?AbstractBlock $parent = null;

    /**
     * @var array<string, mixed> Block data
     */
    private array $data = [];

    /**
     * Set block name.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get block name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Add child block.
     *
     * @param string|null $alias Optional alias for the child
     *
     * @return $this
     */
    public function addChild(self $block, ?string $alias = null): self
    {
        $key = $alias ?? $block->getName() ?? uniqid('child_');

        $this->children[$key] = $block;
        $block->setParent($this);

        return $this;
    }

    /**
     * Get child block by alias.
     */
    public function getChild(string $alias): ?self
    {
        return $this->children[$alias] ?? null;
    }

    /**
     * Get all child blocks.
     *
     * @return array<string, AbstractBlock>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Remove child block.
     *
     * @return $this
     */
    public function removeChild(string $alias): self
    {
        unset($this->children[$alias]);

        return $this;
    }

    /**
     * Set parent block.
     *
     * @return $this
     */
    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent block.
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Set block data.
     *
     * @return $this
     */
    public function setData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get block data.
     */
    public function getData(?string $key = null): mixed
    {
        if (null === $key) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Render block to HTML.
     */
    abstract public function toHtml(): string;

    /**
     * Get child HTML.
     */
    public function getChildHtml(string $alias): string
    {
        $child = $this->getChild($alias);

        return $child ? $child->toHtml() : '';
    }

    /**
     * Get all children HTML concatenated.
     */
    public function getChildrenHtml(): string
    {
        $html = '';

        foreach ($this->children as $child) {
            $html .= $child->toHtml();
        }

        return $html;
    }

    /**
     * Escape HTML content to prevent XSS
     * Use for general content output.
     *
     * @param string|null $value Value to escape
     *
     * @return string Escaped value
     */
    public function escapeHtml(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        return htmlspecialchars($value, \ENT_QUOTES | \ENT_HTML5 | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape HTML attribute values
     * Use for values in HTML attributes (title, alt, data-*, etc.).
     *
     * @param string|null $value Value to escape
     *
     * @return string Escaped value
     */
    public function escapeHtmlAttr(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        return htmlspecialchars($value, \ENT_QUOTES | \ENT_HTML5 | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape URL for safe output
     * Use for href and src attributes.
     *
     * @param string|null $url URL to escape
     *
     * @return string Sanitized URL
     */
    public function escapeUrl(?string $url): string
    {
        if (null === $url || '' === $url) {
            return '';
        }

        // Filter out dangerous protocols
        $filtered = filter_var($url, \FILTER_SANITIZE_URL);

        if (false === $filtered || '' === $filtered) {
            return '';
        }

        // Block javascript: and data: protocols
        if (preg_match('/^(javascript|data):/i', $filtered)) {
            return '';
        }

        return $filtered;
    }

    /**
     * Escape data for JavaScript context
     * Use when outputting PHP data into JavaScript code.
     *
     * @param mixed $value Value to escape
     *
     * @return string JSON-encoded value safe for JS
     *
     * @throws \JsonException
     */
    public function escapeJs(mixed $value): string
    {
        return json_encode($value, \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_THROW_ON_ERROR);
    }

    /**
     * Escape CSS value
     * Use for inline style attributes.
     *
     * @param string|null $value CSS value
     *
     * @return string Escaped value
     */
    public function escapeCss(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        // Remove any HTML tags
        $value = strip_tags($value);

        // Allow only safe characters in CSS
        $result = preg_replace('/[^a-zA-Z0-9\s\-\%\.\,\#\(\)]/', '', $value);

        return null !== $result ? $result : '';
    }
}
