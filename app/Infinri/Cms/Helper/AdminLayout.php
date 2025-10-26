<?php

declare(strict_types=1);

namespace Infinri\Cms\Helper;

/**
 * Admin Layout Helper
 * 
 * Provides shared admin HTML layout structure for all admin pages
 * Eliminates 130+ lines of duplicate HTML/CSS across controllers
 */
class AdminLayout
{
    /**
     * Wrap content in admin HTML structure
     *
     * @param string $content Grid or form HTML content
     * @param string $pageTitle Page title for <title> and <h1>
     * @return string Complete HTML page
     */
    public function wrapContent(string $content, string $pageTitle): string
    {
        $escapedTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$escapedTitle} - Admin</title>
    <link rel="stylesheet" href="/static/adminhtml/css/styles.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
        }
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-header {
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .admin-grid-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }
        .admin-grid-toolbar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .button.primary {
            background: #7c3aed;
        }
        .button.primary:hover {
            background: #6d28d9;
        }
        .status-enabled {
            color: #16a34a;
            font-weight: 600;
        }
        .status-disabled {
            color: #dc2626;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="page-header">
            <h1 class="page-title">{$escapedTitle}</h1>
        </div>
        {$content}
    </div>
</body>
</html>
HTML;
    }
}
