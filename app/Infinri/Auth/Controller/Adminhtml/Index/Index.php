<?php
declare(strict_types=1);

namespace Infinri\Auth\Controller\Adminhtml\Index;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Auth Index Controller
 * 
 * Redirects /admin/auth to /admin/auth/login/index
 */
class Index extends AbstractController
{
    public function execute(): Response
    {
        return $this->redirect('/admin/auth/login/index');
    }
}
