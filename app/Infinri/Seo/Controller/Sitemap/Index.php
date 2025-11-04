<?php

declare(strict_types=1);

namespace Infinri\Seo\Controller\Sitemap;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\ResourceModel\Connection;
use Infinri\Seo\Service\SitemapGenerator;

/**
 * Serves /sitemap.xml.
 */
class Index
{
    /**
     * Execute sitemap generation.
     */
    public function execute(Request $request): Response
    {
        // Build dependencies manually (to avoid ObjectManager)
        $connection = new Connection();

        $pageResource = new \Infinri\Cms\Model\ResourceModel\Page($connection);
        $pageRepository = new PageRepository($pageResource);

        $generator = new SitemapGenerator($pageRepository);

        // Generate sitemap
        $host = $request->getHost();
        // Remove port from host if it's already there
        $hostParts = explode(':', $host);
        $cleanHost = $hostParts[0];

        $baseUrl = $request->getScheme() . '://' . $cleanHost;
        $port = $request->getPort();

        if ($port && ! \in_array($port, [80, 443], true)) {
            $baseUrl .= ':' . $port;
        }

        $xml = $generator->generate($baseUrl);

        // Return XML response
        $response = new Response();
        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->setBody($xml);

        return $response;
    }
}
