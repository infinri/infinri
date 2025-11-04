<?php

declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

use Infinri\Cms\Model\Widget;
use Infinri\Core\Model\ObjectManager;

/**
 * Creates widget block instances by type.
 */
class WidgetFactory
{
    private ObjectManager $objectManager;

    private array $widgetTypes = [
        Widget::TYPE_HTML => Html::class,
        Widget::TYPE_BLOCK => BlockReference::class,
        Widget::TYPE_IMAGE => Image::class,
        Widget::TYPE_VIDEO => Video::class,
    ];

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @throws \InvalidArgumentException if widget type is invalid
     */
    public function create(string $widgetType): AbstractWidget
    {
        if (! isset($this->widgetTypes[$widgetType])) {
            throw new \InvalidArgumentException(\sprintf('Invalid widget type "%s". Valid types: %s', $widgetType, implode(', ', array_keys($this->widgetTypes))));
        }

        $className = $this->widgetTypes[$widgetType];

        /** @var AbstractWidget $widgetBlock */
        $widgetBlock = $this->objectManager->get($className); // @phpstan-ignore-line

        return $widgetBlock;
    }

    public function registerWidgetType(string $type, string $className): void
    {
        $this->widgetTypes[$type] = $className;
    }
}
