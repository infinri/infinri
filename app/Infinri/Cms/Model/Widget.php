<?php

declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Core\Model\AbstractModel;

/**
 * Represents a widget (drag-and-drop content block) on a CMS page.
 */
class Widget extends AbstractModel
{
    public const TYPE_HTML = 'html';

    public const TYPE_BLOCK = 'block';

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const VALID_TYPES = [
        self::TYPE_HTML,
        self::TYPE_BLOCK,
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
    ];

    /**
     * @var ResourceModel\Widget
     */
    protected $resource;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        ResourceModel\Widget $resource,
        array $data = []
    ) {
        $this->resource = $resource;

        parent::__construct($data);
    }

    /**
     * Get resource model.
     */
    public function getResource(): ResourceModel\Widget
    {
        return $this->resource;
    }

    /**
     * Get widget ID.
     */
    public function getWidgetId(): ?int
    {
        $id = $this->getData('widget_id');

        return null !== $id ? (int) $id : null;
    }

    /**
     * Get ID (alias for AbstractSaveController compatibility).
     */
    public function getId(): ?int
    {
        return $this->getWidgetId();
    }

    /**
     * Set widget ID.
     *
     * @return $this
     */
    public function setWidgetId(int $widgetId): self
    {
        return $this->setData('widget_id', $widgetId);
    }

    /**
     * Get page ID.
     */
    public function getPageId(): int
    {
        return (int) $this->getData('page_id');
    }

    /**
     * Set page ID.
     *
     * @return $this
     */
    public function setPageId(int $pageId): self
    {
        return $this->setData('page_id', $pageId);
    }

    /**
     * Get widget type.
     */
    public function getWidgetType(): string
    {
        return (string) $this->getData('widget_type');
    }

    /**
     * Set widget type.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException if invalid type
     */
    public function setWidgetType(string $type): self
    {
        if (! \in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException('Widget type "' . $type . '" is not registered. Available types: ' . implode(', ', self::VALID_TYPES));
        }

        return $this->setData('widget_type', $type);
    }

    /**
     * Get widget data (configuration).
     */
    public function getWidgetData(): array
    {
        $data = $this->getData('widget_data');

        if (\is_string($data)) {
            $decoded = json_decode($data, true);

            return $decoded ?: [];
        }

        return \is_array($data) ? $data : [];
    }

    /**
     * Set widget data (configuration).
     *
     * @param array<string, mixed> $data
     *
     * @return $this
     */
    public function setWidgetData(array $data): self
    {
        return $this->setData('widget_data', json_encode($data));
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
     * Get is active.
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData('is_active');
    }

    /**
     * Set is active.
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
     * Validate widget data.
     *
     * @throws \InvalidArgumentException if validation fails
     */
    public function validate(): void
    {
        // Page ID is required
        if (! $this->getPageId()) {
            throw new \InvalidArgumentException('Page ID is required');
        }

        // Widget type is required and must be valid
        if (! $this->getWidgetType()) {
            throw new \InvalidArgumentException('Widget type is required');
        }

        if (! \in_array($this->getWidgetType(), self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid widget type "%s". Valid types: %s', $this->getWidgetType(), implode(', ', self::VALID_TYPES)));
        }

        // Widget data must be an array (already validated by getWidgetData return type)
        $widgetData = $this->getWidgetData();
        if (empty($widgetData)) {
            throw new \InvalidArgumentException('Widget data cannot be empty');
        }
    }
}
