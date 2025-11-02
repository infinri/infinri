<?php
declare(strict_types=1);

namespace Infinri\Seo\Controller\Robots;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Seo\Service\RobotsGenerator;

/**
 * Robots.txt Controller
 * 
 * Serves /robots.txt
 */
class Index
{
    /**
     * Execute robots.txt generation
     */
    public function execute(Request $request): Response
    {
        // Build dependencies
        $connection = new Connection();
        $generator = new RobotsGenerator($connection);
        
        // Generate robots.txt
        $host = $request->getHost();
        // Remove port from host if it's already there
        $hostParts = explode(':', $host);
        $cleanHost = $hostParts[0];
        
        $baseUrl = $request->getScheme() . '://' . $cleanHost;
        $port = $request->getPort();
        
        if ($port && !in_array($port, [80, 443])) {
            $baseUrl .= ':' . $port;
        }
        
        $content = $generator->generate($baseUrl);
        
        // Return text response
        $response = new Response();
        $response->setHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->setBody($content);
        
        return $response;
    }
}
