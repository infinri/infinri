<?php

declare(strict_types=1);

namespace Infinri\Admin\Model\Menu;

/**
 * Represents a single menu item in the admin navigation.
 */
class Item
{
    private string $id;

    private string $title;

    private ?string $action;

    private ?string $module;

    private ?string $resource;

    private int $sortOrder;

    private ?string $parent;

    /** @var Item[] */
    private array $children = [];

    public function __construct(
        string $id,
        string $title,
        ?string $action = null,
        ?string $module = null,
        ?string $resource = null,
        int $sortOrder = 0,
        ?string $parent = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->action = $action;
        $this->module = $module;
        $this->resource = $resource;
        $this->sortOrder = $sortOrder;
        $this->parent = $parent;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getUrl(): string
    {
        if ($this->action) {
            // Convert action path to URL (e.g., "admin/dashboard" -> "/admin/dashboard")
            return '/' . ltrim($this->action, '/');
        }

        return '#';
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }

    /**
     * @return Item[]
     */
    public function getChildren(): array
    {
        // Sort children by sort order
        usort($this->children, fn ($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());

        return $this->children;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;

        return $this;
    }

    public function isActive(string $currentUrl): bool
    {
        $url = $this->getUrl();

        // Exact match
        if ($url === $currentUrl) {
            return true;
        }

        // Parent is active if any child is active
        foreach ($this->children as $child) {
            if ($child->isActive($currentUrl)) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->getUrl(),
            'action' => $this->action,
            'module' => $this->module,
            'resource' => $this->resource,
            'sortOrder' => $this->sortOrder,
            'parent' => $this->parent,
            'hasChildren' => $this->hasChildren(),
            'children' => array_map(fn ($child) => $child->toArray(), $this->getChildren()),
        ];
    }
}
