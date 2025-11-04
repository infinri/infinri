<?php

declare(strict_types=1);

namespace Infinri\Core\App;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Helper\Logger;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Catches unhandled exceptions and displays user-friendly error pages
 * Returns proper HTTP status codes for SEO.
 */
class ErrorHandler
{
    private PageRepository $pageRepository;

    private LayoutFactory $layoutFactory;

    public function __construct(
        PageRepository $pageRepository,
        LayoutFactory $layoutFactory
    ) {
        $this->pageRepository = $pageRepository;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Handle 500 Internal Server Error.
     */
    public function handle500(\Throwable $exception, Response $response): Response
    {
        Logger::error('500 Internal Server Error', [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $response->setStatusCode(500);

        try {
            // Try to load the CMS 500 page
            $errorPage = $this->pageRepository->getByUrlKey('500');

            if ($errorPage && $errorPage->getData('is_active')) {
                // Render the 500 CMS page
                $html = $this->layoutFactory->render('cms_page_view', [
                    'page' => $errorPage,
                ]);
                $response->setBody($html);
            } else {
                // Fallback: CMS 500 page doesn't exist
                $response->setBody($this->getFallback500Html($exception));
            }
        } catch (\Throwable $e) {
            // If even error handling fails, show basic error
            Logger::error('Error handler itself failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $response->setBody($this->getFallback500Html($exception));
        }

        return $response;
    }

    /**
     * Handle 404 Not Found Error.
     *
     * @param string $path Requested path that wasn't found
     */
    public function handle404(Response $response, string $path = ''): Response
    {
        Logger::info('404 Not Found', ['path' => $path]);

        $response->setStatusCode(404);

        try {
            // Load the CMS 404 page
            $errorPage = $this->pageRepository->getByUrlKey('404');

            if ($errorPage && $errorPage->getData('is_active')) {
                // Render the 404 CMS page
                $html = $this->layoutFactory->render('cms_page_view', [
                    'page' => $errorPage,
                ]);
                $response->setBody($html);
            } else {
                // Fallback: CMS 404 page doesn't exist
                $response->setBody($this->getFallback404Html($path));
            }
        } catch (\Throwable $e) {
            Logger::error('404 handler failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $response->setBody($this->getFallback404Html($path));
        }

        return $response;
    }

    /**
     * Get fallback 500 HTML when CMS page isn't available.
     */
    private function getFallback500Html(\Throwable $exception): string
    {
        $showDetails = $_ENV['APP_DEBUG'] ?? false;

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>500 - Internal Server Error</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #d32f2f; }
        .details { background: #f5f5f5; padding: 15px; border-radius: 4px; margin: 20px 0; }
        code { background: #e0e0e0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>500 - Internal Server Error</h1>
    <p>We\'re sorry, but something went wrong on our end. Our team has been notified and is working to fix the issue.</p>
    <p>Please try again later. If the problem persists, please contact support.</p>';

        if ($showDetails) {
            $html .= '
    <div class="details">
        <h2>Error Details (Debug Mode)</h2>
        <p><strong>Exception:</strong> ' . htmlspecialchars($exception::class) . '</p>
        <p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>
        <p><strong>File:</strong> <code>' . htmlspecialchars($exception->getFile()) . '</code> (Line ' . $exception->getLine() . ')</p>
    </div>';
        }

        $html .= '
    <p><a href="/">← Return to Homepage</a></p>
</body>
</html>';

        return $html;
    }

    /**
     * Get fallback 404 HTML when CMS page isn't available.
     */
    private function getFallback404Html(string $path): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; text-align: center; }
        h1 { color: #1976d2; font-size: 4em; margin: 0; }
        h2 { color: #424242; }
        p { color: #666; font-size: 1.1em; }
        a { color: #1976d2; text-decoration: none; font-weight: 500; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page <code>' . htmlspecialchars($path) . '</code> could not be found.</p>
    <p>It may have been moved, deleted, or never existed.</p>
    <p><a href="/">← Go to Homepage</a></p>
</body>
</html>';
    }
}
