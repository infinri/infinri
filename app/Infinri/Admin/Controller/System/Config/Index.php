<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * System Configuration Controller
 */
class Index
{
    private LayoutFactory $layoutFactory;
    
    public function __construct(
        LayoutFactory $layoutFactory
    ) {
        $this->layoutFactory = $layoutFactory;
    }
    
    /**
     * Display configuration page
     */
    public function execute(Request $request): Response
    {
        // Render using layout system
        $html = $this->layoutFactory->render('admin_system_config_index');
        
        return (new Response())->setBody($html);
    }
}
