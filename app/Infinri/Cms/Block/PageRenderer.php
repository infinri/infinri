<?php

declare(strict_types=1);

namespace Infinri\Cms\Block;

use Infinri\Cms\Block\Widget\WidgetFactory;
use Infinri\Cms\Model\Repository\WidgetRepository;
use Infinri\Core\Block\Template;
use Infinri\Core\Helper\Logger;

/**
 * Renders a CMS page with all its widgets in correct order.
 */
class PageRenderer extends Template
{
    private WidgetRepository $widgetRepository;

    private WidgetFactory $widgetFactory;

    public function __construct(
        WidgetRepository $widgetRepository,
        WidgetFactory $widgetFactory
    ) {
        $this->widgetRepository = $widgetRepository;
        $this->widgetFactory = $widgetFactory;
    }

    public function renderPageWidgets(int $pageId): string
    {
        try {
            $widgets = $this->widgetRepository->getByPageId($pageId, true);

            if (empty($widgets)) {
                return '';
            }

            $output = '';

            foreach ($widgets as $widget) {
                try {
                    $widgetBlock = $this->widgetFactory->create($widget->getWidgetType());
                    $widgetBlock->setWidget($widget);
                    $output .= $widgetBlock->toHtml();
                } catch (\Exception $e) {
                    // Log error but continue rendering other widgets
                    Logger::warning('Error rendering widget', [
                        'widget_id' => $widget->getWidgetId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $output;
        } catch (\Exception $e) {
            Logger::error('Error rendering widgets for page', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
