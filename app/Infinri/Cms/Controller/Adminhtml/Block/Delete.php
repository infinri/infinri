<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Block;

use Infinri\Cms\Controller\Adminhtml\AbstractDeleteController;
use Infinri\Cms\Model\Repository\BlockRepository;

class Delete extends AbstractDeleteController
{
    public function __construct(
        private readonly BlockRepository $blockRepository
    ) {}

    protected function getRepository(): BlockRepository
    {
        return $this->blockRepository;
    }

    protected function getIdParam(): string
    {
        return 'id';
    }

    protected function getIndexRoute(): string
    {
        return '/admin/cms/block/index';
    }

    protected function getEntityName(): string
    {
        return 'block';
    }
}
