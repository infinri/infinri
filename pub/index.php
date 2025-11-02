<?php
/**
 * Application Entry Point
 * 
 * This file is the main entry point for all HTTP requests.
 */

declare(strict_types=1);

// Serve static files directly (for PHP built-in server or when .htaccess doesn't work)
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Ignore favicon requests to prevent layout pollution
if ($requestUri === '/favicon.ico' || str_ends_with($requestUri, '/favicon.ico')) {
    http_response_code(404);
    exit;
}

if (preg_match('/^\/(static|media)\//', $requestUri)) {
    $filePath = __DIR__ . parse_url($requestUri, PHP_URL_PATH);
    if (file_exists($filePath) && is_file($filePath)) {
        // Determine MIME type
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
        readfile($filePath);
        exit;
    } else {
        // File not found
        http_response_code(404);
        echo '404 Not Found: ' . htmlspecialchars($requestUri);
        exit;
    }
}

use Infinri\Core\App\Request;
use Infinri\Core\Helper\Logger;
use Dotenv\Dotenv;

// Load autoloader FIRST - before using any classes
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file BEFORE checking APP_ENV
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Enable error reporting based on environment
$env = getenv('APP_ENV') ?: $_ENV['APP_ENV'] ?? 'production';
$isDevelopment = in_array($env, ['development', 'dev', 'local']);

if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

// Initialize logger early
Logger::init(__DIR__ . '/../var/log');

// Rotate logs if needed (happens once per request, quick check)
Logger::rotateLogsIfNeeded();

// Set up error and exception handlers (Logger is now available)
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Log the error
    Logger::error("PHP Error: $message", [
        'severity' => $severity,
        'file' => $file,
        'line' => $line
    ]);
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    // Log the exception
    Logger::exception($exception, 'Uncaught exception');
    
    // Display error page
    http_response_code(500);
    
    if (ini_get('display_errors')) {
        echo '<h1>500 Internal Server Error</h1>';
        echo '<h2>' . htmlspecialchars(get_class($exception)) . '</h2>';
        echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>500 Internal Server Error</h1>';
        echo '<p>An error occurred. Please check the error log.</p>';
    }
    
    exit(1);
});

try {
    // Bootstrap the application
    $frontController = require __DIR__ . '/../app/bootstrap.php';
    $frontController = initApplication();
    
    Logger::info('Application started', ['uri' => $_SERVER['REQUEST_URI'] ?? '/']);
    
    // Create request from globals
    $request = Request::createFromGlobals();
    
    // Dispatch request to controller
    $response = $frontController->dispatch($request);
    
    // Send response to browser
    $response->send();
    
    Logger::info('Request completed successfully');
    
} catch (\Throwable $e) {
    // Log the exception
    Logger::exception($e, 'Fatal error during request');
    
    // Re-throw to be caught by exception handler
    throw $e;
}
