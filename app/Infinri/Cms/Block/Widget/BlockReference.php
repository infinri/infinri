<?php
declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

use Infinri\Cms\Model\Repository\BlockRepository;

/**
 * Embeds a CMS block into a page
 */
class BlockReference extends AbstractWidget
{
    /**
     * @var BlockRepository
     */
    private BlockRepository $blockRepository;
    
    /**
     * @param BlockRepository $blockRepository
     */
    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }
    
    /**
     * Render block reference widget
     *
     * @return string
     */
    public function toHtml(): string
    {
        $data = $this->getWidgetData();
        $identifier = $data['block_identifier'] ?? null;
        
        if (!$identifier) {
            return '';
        }
        
        try {
            $block = $this->blockRepository->getByIdentifier($identifier);
            
            if (!$block->isActive()) {
                return '';
            }
            
            return sprintf(
                '<div class="widget widget-block" data-widget-id="%d" data-widget-type="block" data-block-identifier="%s">%s</div>',
                $this->getWidgetId() ?? 0,
                $this->escapeHtmlAttr($identifier),
                $block->getContent()
            );
        } catch (\RuntimeException $e) {
            // Block not found - fail silently in production
            return '';
        }
    }
}
