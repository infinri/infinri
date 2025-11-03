<?php
declare(strict_types=1);

namespace Infinri\Cms\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;
use PDO;

/**
 * Creates essential CMS pages: home, 404, 500, maintenance
 */
class InstallDefaultCmsPages implements DataPatchInterface
{
    private PDO $connection;
    
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * @inheritDoc
     */
    public function apply(): void
    {
        $pages = $this->getDefaultPages();
        
        foreach ($pages as $page) {
            // Check if page already exists
            $stmt = $this->connection->prepare(
                "SELECT page_id FROM cms_page WHERE url_key = ?"
            );
            $stmt->execute([$page['url_key']]);
            
            if ($stmt->fetchColumn()) {
                continue; // Skip if exists
            }
            
            // Insert page
            $stmt = $this->connection->prepare(
                "INSERT INTO cms_page (title, url_key, content, meta_title, meta_description, is_active, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );
            
            $stmt->execute([
                $page['title'],
                $page['url_key'],
                $page['content'],
                $page['meta_title'],
                $page['meta_description'],
                $page['is_active'],
            ]);
        }
    }
    
    /**
     * Get default pages data
     */
    private function getDefaultPages(): array
    {
        return [
            [
                'title' => 'Home',
                'url_key' => 'home',
                'meta_title' => 'Home - Infinri Portfolio',
                'content' => $this->getHomeContent(),
                'meta_description' => 'Welcome to Infinri Portfolio',
                'is_active' => 1,
            ],
            [
                'title' => '404 Not Found',
                'url_key' => '404',
                'meta_title' => '404 Not Found',
                'content' => $this->get404Content(),
                'meta_description' => 'Page not found',
                'is_active' => 1,
            ],
            [
                'title' => '500 Server Error',
                'url_key' => '500',
                'meta_title' => '500 Server Error',
                'content' => $this->get500Content(),
                'meta_description' => 'Server error',
                'is_active' => 1,
            ],
            [
                'title' => 'Maintenance Mode',
                'url_key' => 'maintenance',
                'meta_title' => 'Site Under Maintenance',
                'content' => $this->getMaintenanceContent(),
                'meta_description' => 'Site under maintenance',
                'is_active' => 1,
            ],
        ];
    }
    
    /**
     * Get home page content
     */
    private function getHomeContent(): string
    {
        return <<<HTML
<h1>Welcome to Infinri</h1>

<p>This is your homepage. You can edit this content through the admin panel.</p>

<h2>About This Site</h2>
<p>Infinri is built with a professional Magento-style architecture, featuring:</p>

<ul>
    <li>Modular design with separation of concerns</li>
    <li>Flexible layout system with XML configuration</li>
    <li>Themeable templates and styles</li>
    <li>CMS content management</li>
    <li>Clean, semantic HTML output</li>
</ul>

<h2>Get Started</h2>
<p>Visit the admin panel to manage your content and customize your site.</p>
HTML;
    }
    
    /**
     * Get 404 page content
     */
    private function get404Content(): string
    {
        return <<<HTML
<h1>404 - Page Not Found</h1>

<p>Sorry, the page you are looking for could not be found.</p>

<p>The page may have been moved, deleted, or the URL may be incorrect.</p>

<h2>What can you do?</h2>
<ul>
    <li>Check the URL for typos</li>
    <li>Return to the <a href="/">homepage</a></li>
    <li>Use the navigation menu to find what you're looking for</li>
</ul>
HTML;
    }
    
    /**
     * Get 500 page content
     */
    private function get500Content(): string
    {
        return <<<HTML
<h1>500 - Server Error</h1>

<p>Sorry, something went wrong on our end.</p>

<p>We're working to fix the issue. Please try again later.</p>

<h2>What can you do?</h2>
<ul>
    <li>Try refreshing the page</li>
    <li>Return to the <a href="/">homepage</a></li>
    <li>Contact support if the problem persists</li>
</ul>
HTML;
    }
    
    /**
     * Get maintenance page content
     */
    private function getMaintenanceContent(): string
    {
        return <<<HTML
<h1>Site Under Maintenance</h1>

<p>We're currently performing scheduled maintenance to improve your experience.</p>

<p>We'll be back shortly. Thank you for your patience!</p>

<h2>Estimated Downtime</h2>
<p>Maintenance is expected to be completed within a few hours.</p>

<p>Please check back soon.</p>
HTML;
    }
    
    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
    
    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
