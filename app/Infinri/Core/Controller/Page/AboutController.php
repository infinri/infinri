<?php
declare(strict_types=1);

namespace Infinri\Core\Controller\Page;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Response;

/**
 * About Page Controller
 */
class AboutController extends AbstractController
{
    /**
     * About page action
     *
     * @return Response
     */
    public function execute(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>About - Infinri Framework</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2563eb; }
    </style>
</head>
<body>
    <h1>About Infinri Framework</h1>
    <p>Infinri is a modern PHP MVC framework built with Test-Driven Development.</p>
    <h2>Technology Stack</h2>
    <ul>
        <li>PHP 8.1+</li>
        <li>PHP-DI for Dependency Injection</li>
        <li>PSR-11 Container Interface</li>
        <li>Pest for Testing</li>
    </ul>
    <p><a href="/">← Back to Home</a></p>
</body>
</html>
HTML;
        
        return $this->response->setBody($html);
    }
}
