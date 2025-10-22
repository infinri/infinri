<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\Adminhtml\Index;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;
use Infinri\Core\Helper\Logger;

/**
 * Admin Dashboard Controller
 */
class Index extends AbstractController
{
    public function __construct(
        Request $request,
        Response $response,
        private readonly LayoutFactory $layoutFactory
    ) {
        parent::__construct($request, $response);
    }

    public function execute(): Response
    {
        Logger::info('Admin Dashboard accessed', [
            'user' => $_SESSION['admin_username'] ?? 'unknown'
        ]);

        // Render dashboard using layout
        $html = $this->layoutFactory->render('admin_adminhtml_index_index', [
            'user' => [
                'username' => $_SESSION['admin_username'] ?? '',
                'email' => $_SESSION['admin_email'] ?? '',
                'full_name' => $_SESSION['admin_full_name'] ?? '',
                'roles' => $_SESSION['admin_roles'] ?? [],
            ]
        ]);

        return $this->response->setBody($html);
    }
}
