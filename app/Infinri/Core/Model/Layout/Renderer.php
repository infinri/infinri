<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Layout;

use Infinri\Core\Block\AbstractBlock;

/**
 * Renders a block tree to HTML.
 */
class Renderer
{
    /**
     * Render block tree to HTML.
     *
     * @return string HTML output
     */
    public function render(AbstractBlock $rootBlock): string
    {
        return $rootBlock->toHtml();
    }

    /**
     * Render specific block by name.
     *
     * @return string HTML output
     */
    public function renderBlock(Builder $builder, string $blockName): string
    {
        $block = $builder->getBlock($blockName);

        if (null === $block) {
            return '';
        }

        return $block->toHtml();
    }
}
