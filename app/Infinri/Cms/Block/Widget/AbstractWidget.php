<?php
declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

use Infinri\Core\Block\Template;
use Infinri\Cms\Model\Widget;

/**
 * Base class for all widget renderer blocks
 */
abstract class AbstractWidget extends Template
{
    /**
     * @var Widget
     */
    protected Widget $widget;
    
    /**
     * @param Widget $widget
     * @return $this
     */
    public function setWidget(Widget $widget): self
    {
        $this->widget = $widget;
        return $this;
    }
    
    /**
     * @return Widget
     */
    public function getWidget(): Widget
    {
        return $this->widget;
    }
    
    /**
     * @return array
     */
    public function getWidgetData(): array
    {
        return $this->widget->getWidgetData();
    }
    
    /**
     * @return int|null
     */
    public function getWidgetId(): ?int
    {
        return $this->widget->getWidgetId();
    }
    
    /**
     * Render widget HTML
     * 
     * Child classes should override this method to provide widget-specific rendering
     *
     * @return string
     */
    public function toHtml(): string
    {
        return '';
    }
}
