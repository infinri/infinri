<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Core\View\Element\UiFormRenderer;
use Infinri\Cms\Controller\Adminhtml\AbstractEditController;

/**
 * Edit/Create Page Controller
 * Follows Magento pattern: edit?id=123 (edit) or edit (new)
 */
class Edit extends AbstractEditController
{
    public function __construct(UiFormRenderer $formRenderer)
    {
        parent::__construct($formRenderer);
    }

    protected function getFormName(): string
    {
        return 'cms_page_form';
    }

    protected function getIdParam(): string
    {
        return 'id';
    }
}
