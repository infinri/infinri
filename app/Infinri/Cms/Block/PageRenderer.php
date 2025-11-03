<?php
declare(strict_types=1);

namespace Infinri\Cms\Block;

use Infinri\Core\Block\Template;
use Infinri\Cms\Model\Repository\WidgetRepository;
use Infinri\Cms\Block\Widget\WidgetFactory;

/**
 * Renders a CMS page with all its widgets in correct order
 */
class PageRenderer extends Template
{
    /**
     * @var WidgetRepository
     */
    private WidgetRepository $widgetRepository;

    /**
     * @var WidgetFactory
     */
    private WidgetFactory $widgetFactory;

    /**
     * @param WidgetRepository $widgetRepository
     * @param WidgetFactory $widgetFactory
     */
    public function __construct(
        WidgetRepository $widgetRepository,
        WidgetFactory    $widgetFactory
    ) {
        $this->widgetRepository = $widgetRepository;
        $this->widgetFactory = $widgetFactory;
    }

    /**
     * @param int $pageId
     * @return string
     */
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
                    error_log(sprintf(
                        'Error rendering widget %d: %s',
                        $widget->getWidgetId(),
                        $e->getMessage()
                    ));
                }
            }

            return $output;
        } catch (\Exception $e) {
            error_log(sprintf(
                'Error rendering widgets for page %d: %s',
                $pageId,
                $e->getMessage()
            ));
            return '';
        }
    }
}
