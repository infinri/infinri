<?php
declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

/**
 * Renders custom HTML content
 */
class Html extends AbstractWidget
{
    /**
     * Render HTML widget
     *
     * @return string
     */
    public function toHtml(): string
    {
        $data = $this->getWidgetData();
        $htmlContent = $data['html_content'] ?? '';

        if (empty($htmlContent)) {
            return '';
        }

        return sprintf(
            '<div class="widget widget-html" data-widget-id="%d" data-widget-type="html">%s</div>',
            $this->getWidgetId() ?? 0,
            $htmlContent  // Already HTML, don't escape
        );
    }
}
