<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Core\View\Element\UiFormRenderer;
use Infinri\Cms\Controller\Adminhtml\AbstractEditController;

/**
 * Edit/Create Block Controller
 */
class Edit extends AbstractEditController
{
    public function __construct(UiFormRenderer $formRenderer)
    {
        parent::__construct($formRenderer);
    }

    protected function getFormName(): string
    {
        return 'cms_block_form';
    }

    protected function getIdParam(): string
    {
        return 'id';
    }
}
