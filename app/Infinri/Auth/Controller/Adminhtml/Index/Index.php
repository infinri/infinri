<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Index;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Auth Index Controller
 * Route: /admin/auth/index
 * Redirects to login page
 */
class Index
{
    public function execute(Request $request): Response
    {
        $response = new Response();
        $response->setStatusCode(302);
        $response->setHeader('Location', '/admin/auth/login/index');
        return $response;
    }
}
