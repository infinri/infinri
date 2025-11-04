<?php

declare(strict_types=1);

namespace Infinri\Cms\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Handles database operations for widgets.
 */
class Widget extends AbstractResource
{
    /**
     * Resource initialization.
     */
    protected function _construct(): void
    {
        $this->_init('cms_page_widget', 'widget_id');
    }
}
