<?php
declare(strict_types=1);

namespace Infinri\Core\Controller\Api;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Response;

/**
 * API Test Controller
 */
class TestController extends AbstractController
{
    /**
     * API test action
     *
     * @return Response
     */
    public function execute(): Response
    {
        $data = [
            'status' => 'success',
            'message' => 'API is working!',
            'framework' => 'Infinri',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'request' => [
                'method' => $this->request->getMethod(),
                'path' => $this->request->getPathInfo(),
                'ip' => $this->request->getClientIp(),
            ],
        ];
        
        return $this->json($data);
    }
}
