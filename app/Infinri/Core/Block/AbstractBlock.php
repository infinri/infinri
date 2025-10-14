<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * Abstract Block
 * 
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
     * Set block name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get block name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Add child block
     *
     * @param AbstractBlock $block
     * @param string|null $alias Optional alias for the child
     * @return $this
     */
    public function addChild(AbstractBlock $block, ?string $alias = null): self
    {
        $key = $alias ?? $block->getName() ?? uniqid('child_');
        
        $this->children[$key] = $block;
        $block->setParent($this);
        
        return $this;
    }

    /**
     * Get child block by alias
     *
     * @param string $alias
     * @return AbstractBlock|null
     */
    public function getChild(string $alias): ?AbstractBlock
    {
        return $this->children[$alias] ?? null;
    }

    /**
     * Get all child blocks
     *
     * @return array<string, AbstractBlock>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Remove child block
     *
     * @param string $alias
     * @return $this
     */
    public function removeChild(string $alias): self
    {
        unset($this->children[$alias]);
        return $this;
    }

    /**
     * Set parent block
     *
     * @param AbstractBlock|null $parent
     * @return $this
     */
    public function setParent(?AbstractBlock $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get parent block
     *
     * @return AbstractBlock|null
     */
    public function getParent(): ?AbstractBlock
    {
        return $this->parent;
    }

    /**
     * Set block data
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get block data
     *
     * @param string|null $key
     * @return mixed
     */
    public function getData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }
        
        return $this->data[$key] ?? null;
    }

    /**
     * Render block to HTML
     *
     * @return string
     */
    abstract public function toHtml(): string;

    /**
     * Get child HTML
     *
     * @param string $alias
     * @return string
     */
    public function getChildHtml(string $alias): string
    {
        $child = $this->getChild($alias);
        
        return $child ? $child->toHtml() : '';
    }

    /**
     * Get all children HTML concatenated
     *
     * @return string
     */
    public function getChildrenHtml(): string
    {
        $html = '';
        
        foreach ($this->children as $child) {
            $html .= $child->toHtml();
        }
        
        return $html;
    }
}
