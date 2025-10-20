<?php

declare(strict_types=1);

namespace Infinri\Admin\Controller\Dashboard;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Admin Dashboard
 * 
 * Main landing page for admin panel
 */
class Index
{
    private LayoutFactory $layoutFactory;
    
    public function __construct(LayoutFactory $layoutFactory)
    {
        $this->layoutFactory = $layoutFactory;
    }
    
    public function execute(Request $request): Response
    {
        $response = new Response();
        
        // Render using layout system
        $html = $this->layoutFactory->render('admin_dashboard_index');
        
        return $response->setBody($html);
    }
}
