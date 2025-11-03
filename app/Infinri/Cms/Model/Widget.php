<?php
declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Core\Model\AbstractModel;

/**
 * Represents a widget (drag-and-drop content block) on a CMS page
 */
class Widget extends AbstractModel
{
    const TYPE_HTML = 'html';
    const TYPE_BLOCK = 'block';
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';

    const VALID_TYPES = [
        self::TYPE_HTML,
        self::TYPE_BLOCK,
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
    ];

    /**
     * @var \Infinri\Cms\Model\ResourceModel\Widget
     */
    protected $resource;

    /**
     * Constructor
     *
     * @param \Infinri\Cms\Model\ResourceModel\Widget $resource
     * @param array $data
     */
    public function __construct(
        \Infinri\Cms\Model\ResourceModel\Widget $resource,
        array                                   $data = []
    ) {
        $this->resource = $resource;

        parent::__construct($data);
    }

    /**
     * Get resource model
     *
     * @return \Infinri\Cms\Model\ResourceModel\Widget
     */
    public function getResource(): \Infinri\Cms\Model\ResourceModel\Widget
    {
        return $this->resource;
    }

    /**
     * Get widget ID
     *
     * @return int|null
     */
    public function getWidgetId(): ?int
    {
        $id = $this->getData('widget_id');
        return $id !== null ? (int)$id : null;
    }

    /**
     * Get ID (alias for AbstractSaveController compatibility)
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getWidgetId();
    }

    /**
     * Set widget ID
     *
     * @param int $widgetId
     * @return $this
     */
    public function setWidgetId(int $widgetId): self
    {
        return $this->setData('widget_id', $widgetId);
    }

    /**
     * Get page ID
     *
     * @return int
     */
    public function getPageId(): int
    {
        return (int)$this->getData('page_id');
    }

    /**
     * Set page ID
     *
     * @param int $pageId
     * @return $this
     */
    public function setPageId(int $pageId): self
    {
        return $this->setData('page_id', $pageId);
    }

    /**
     * Get widget type
     *
     * @return string
     */
    public function getWidgetType(): string
    {
        return (string)$this->getData('widget_type');
    }

    /**
     * Set widget type
     *
     * @param string $type
     * @return $this
     * @throws \InvalidArgumentException if invalid type
     */
    public function setWidgetType(string $type): self
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid widget type "%s". Valid types: %s',
                    $type,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }
        return $this->setData('widget_type', $type);
    }

    /**
     * Get widget data (configuration)
     *
     * @return array
     */
    public function getWidgetData(): array
    {
        $data = $this->getData('widget_data');

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return $decoded ?: [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Set widget data (configuration)
     *
     * @param array $data
     * @return $this
     */
    public function setWidgetData(array $data): self
    {
        return $this->setData('widget_data', json_encode($data));
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData('sort_order');
    }

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData('sort_order', $sortOrder);
    }

    /**
     * Get is active
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData('is_active');
    }

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Get created at timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }

    /**
     * Get updated at timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData('updated_at');
    }

    /**
     * Validate widget data
     *
     * @return void
     * @throws \InvalidArgumentException if validation fails
     */
    public function validate(): void
    {
        // Page ID is required
        if (!$this->getPageId()) {
            throw new \InvalidArgumentException('Page ID is required');
        }

        // Widget type is required and must be valid
        if (!$this->getWidgetType()) {
            throw new \InvalidArgumentException('Widget type is required');
        }

        if (!in_array($this->getWidgetType(), self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid widget type "%s". Valid types: %s',
                    $this->getWidgetType(),
                    implode(', ', self::VALID_TYPES)
                )
            );
        }

        // Widget data must be an array
        if (!is_array($this->getWidgetData())) {
            throw new \InvalidArgumentException('Widget data must be an array');
        }
    }
}
