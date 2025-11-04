<?php

declare(strict_types=1);

namespace Infinri\Menu\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Menu\Model\ResourceModel\MenuItem as MenuItemResource;

/**
 * Represents an individual menu item within a menu.
 */
class MenuItem extends AbstractModel
{
    /** Link Types */
    public const LINK_TYPE_CMS_PAGE = 'cms_page';
    public const LINK_TYPE_CATEGORY = 'category';
    public const LINK_TYPE_CUSTOM_URL = 'custom_url';
    public const LINK_TYPE_EXTERNAL = 'external';

    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        protected readonly MenuItemResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get resource model.
     */
    protected function getResource(): MenuItemResource
    {
        return $this->resource;
    }

    /**
     * Get item ID.
     */
    public function getItemId(): ?int
    {
        $value = $this->getData('item_id');

        return null !== $value ? (int) $value : null;
    }

    /**
     * Set item ID.
     *
     * @return $this
     */
    public function setItemId(int $id): self
    {
        return $this->setData('item_id', $id);
    }

    /**
     * Get menu ID.
     */
    public function getMenuId(): ?int
    {
        $value = $this->getData('menu_id');

        return null !== $value ? (int) $value : null;
    }

    /**
     * Set menu ID.
     *
     * @return $this
     */
    public function setMenuId(int $menuId): self
    {
        return $this->setData('menu_id', $menuId);
    }

    /**
     * Get parent item ID.
     */
    public function getParentItemId(): ?int
    {
        $value = $this->getData('parent_item_id');

        return null !== $value ? (int) $value : null;
    }

    /**
     * Set parent item ID.
     *
     * @return $this
     */
    public function setParentItemId(?int $parentItemId): self
    {
        return $this->setData('parent_item_id', $parentItemId);
    }

    /**
     * Get title.
     */
    public function getTitle(): ?string
    {
        return $this->getData('title');
    }

    /**
     * Set title.
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        return $this->setData('title', $title);
    }

    /**
     * Get link type.
     */
    public function getLinkType(): ?string
    {
        return $this->getData('link_type');
    }

    /**
     * Set link type.
     *
     * @return $this
     */
    public function setLinkType(string $linkType): self
    {
        return $this->setData('link_type', $linkType);
    }

    /**
     * Get resource ID.
     */
    public function getResourceId(): ?int
    {
        $value = $this->getData('resource_id');

        return null !== $value ? (int) $value : null;
    }

    /**
     * Set resource ID.
     *
     * @return $this
     */
    public function setResourceId(?int $resourceId): self
    {
        return $this->setData('resource_id', $resourceId);
    }

    /**
     * Get custom URL.
     */
    public function getCustomUrl(): ?string
    {
        return $this->getData('custom_url');
    }

    /**
     * Set custom URL.
     *
     * @return $this
     */
    public function setCustomUrl(?string $customUrl): self
    {
        return $this->setData('custom_url', $customUrl);
    }

    /**
     * Get CSS class.
     */
    public function getCssClass(): ?string
    {
        return $this->getData('css_class');
    }

    /**
     * Set CSS class.
     *
     * @return $this
     */
    public function setCssClass(?string $cssClass): self
    {
        return $this->setData('css_class', $cssClass);
    }

    /**
     * Get icon class.
     */
    public function getIconClass(): ?string
    {
        return $this->getData('icon_class');
    }

    /**
     * Set icon class.
     *
     * @return $this
     */
    public function setIconClass(?string $iconClass): self
    {
        return $this->setData('icon_class', $iconClass);
    }

    /**
     * Check if opens in new tab.
     */
    public function getOpenInNewTab(): bool
    {
        return (bool) $this->getData('open_in_new_tab');
    }

    /**
     * Set open in new tab.
     *
     * @return $this
     */
    public function setOpenInNewTab(bool $openInNewTab): self
    {
        return $this->setData('open_in_new_tab', $openInNewTab);
    }

    /**
     * Get sort order.
     */
    public function getSortOrder(): int
    {
        return (int) $this->getData('sort_order');
    }

    /**
     * Set sort order.
     *
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData('sort_order', $sortOrder);
    }

    /**
     * Check if menu item is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set active status.
     *
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get created at timestamp.
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    /**
     * Validate menu item data.
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        $errors = [];

        // Validate title
        if (empty($this->getTitle())) {
            $errors[] = 'Menu item title is required';
        }

        // Validate menu_id
        if (empty($this->getMenuId())) {
            $errors[] = 'Menu ID is required';
        }

        // Validate link_type
        $linkType = $this->getLinkType();
        if (empty($linkType)) {
            $errors[] = 'Link type is required';
        } elseif (! \in_array($linkType, [self::LINK_TYPE_CMS_PAGE, self::LINK_TYPE_CATEGORY, self::LINK_TYPE_CUSTOM_URL, self::LINK_TYPE_EXTERNAL], true)) {
            $errors[] = 'Invalid link type';
        }

        // Validate based on link type
        if (self::LINK_TYPE_CMS_PAGE === $linkType || self::LINK_TYPE_CATEGORY === $linkType) {
            if (empty($this->getResourceId())) {
                $errors[] = 'Resource ID is required for ' . $linkType;
            }
        }

        if (self::LINK_TYPE_CUSTOM_URL === $linkType || self::LINK_TYPE_EXTERNAL === $linkType) {
            if (empty($this->getCustomUrl())) {
                $errors[] = 'Custom URL is required for ' . $linkType;
            }
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException('Menu item validation failed: ' . implode(', ', $errors));
        }
    }
}
