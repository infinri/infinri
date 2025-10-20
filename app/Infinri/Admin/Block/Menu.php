<?php

declare(strict_types=1);

namespace Infinri\Admin\Block;

use Infinri\Core\Block\Template;
use Infinri\Admin\Model\Menu\Builder;
use Infinri\Core\App\Request;

/**
 * Admin Menu Block
 * 
 * Renders admin navigation menu
 */
class Menu extends Template
{
    private Builder $menuBuilder;
    private Request $request;
    
    public function __construct(
        Builder $menuBuilder,
        Request $request
    ) {
        $this->menuBuilder = $menuBuilder;
        $this->request = $request;
    }
    
    /**
     * Get menu items
     *
     * @return \Infinri\Admin\Model\Menu\Item[]
     */
    public function getMenuItems(): array
    {
        return $this->menuBuilder->build();
    }
    
    /**
     * Get current URL for active state detection
     */
    public function getCurrentUrl(): string
    {
        return $this->request->getRequestUri();
    }
}
