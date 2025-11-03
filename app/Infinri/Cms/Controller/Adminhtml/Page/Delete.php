<?php
declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Page;

use Infinri\Cms\Controller\Adminhtml\AbstractDeleteController;
use Infinri\Cms\Model\Repository\PageRepository;

class Delete extends AbstractDeleteController
{
    public function __construct(
        private readonly PageRepository $pageRepository
    ) {}

    protected function getRepository(): PageRepository
    {
        return $this->pageRepository;
    }

    protected function getIdParam(): string
    {
        return 'id';
    }

    protected function getIndexRoute(): string
    {
        return '/admin/cms/page/index';
    }

    protected function getEntityName(): string
    {
        return 'page';
    }
}
