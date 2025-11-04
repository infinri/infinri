<?php

declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

use Infinri\Cms\Model\Widget;
use Infinri\Core\Block\Template;

/**
 * Base class for all widget renderer blocks.
 */
abstract class AbstractWidget extends Template
{
    protected ?Widget $widget = null;

    /**
     * @return $this
     */
    public function setWidget(Widget $widget): self
    {
        $this->widget = $widget;

        return $this;
    }

    public function getWidget(): ?Widget
    {
        return $this->widget;
    }

    public function getWidgetData(): array
    {
        return $this->widget->getWidgetData();
    }

    public function getWidgetId(): ?int
    {
        return $this->widget->getWidgetId();
    }

    /**
     * Render widget HTML
     * Child classes should override this method to provide widget-specific rendering.
     */
    public function toHtml(): string
    {
        return '';
    }
}
