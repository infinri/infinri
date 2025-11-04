<?php

declare(strict_types=1);

namespace Infinri\Seo\Service;

use Infinri\Core\Model\ResourceModel\Connection;

/**
 * Generates robots.txt content from database or defaults.
 */
class RobotsGenerator
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Generate robots.txt content.
     *
     * @param string $baseUrl Base URL for sitemap reference
     *
     * @return string robots.txt content
     */
    public function generate(string $baseUrl): string
    {
        // Try to get custom robots.txt from database
        $customRobots = $this->getCustomRobots();

        if ($customRobots) {
            return $customRobots;
        }

        // Return default robots.txt
        return $this->getDefaultRobots($baseUrl);
    }

    /**
     * Get custom robots.txt from database.
     *
     * @return string|null Custom robots.txt content or null
     */
    private function getCustomRobots(): ?string
    {
        try {
            $pdo = $this->connection->getConnection();
            $stmt = $pdo->query('
                SELECT content 
                FROM seo_robots 
                WHERE is_active = true 
                ORDER BY robots_id DESC 
                LIMIT 1
            ');

            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? $result['content'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get default robots.txt content.
     *
     * @param string $baseUrl Base URL
     *
     * @return string Default robots.txt
     */
    private function getDefaultRobots(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        return <<<ROBOTS
# Robots.txt for Infinri
User-agent: *
Disallow: /admin
Disallow: /admin/*
Disallow: /var/
Disallow: /app/

# Allow all other pages
Allow: /

# Sitemap
Sitemap: {$baseUrl}/sitemap.xml
ROBOTS;
    }
}
