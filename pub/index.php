<?php
/**
 * Application Entry Point
 * 
 * This file is the main entry point for all HTTP requests.
 */

declare(strict_types=1);

use Infinri\Core\App\Request;
use Infinri\Core\Helper\Logger;

// Load autoloader FIRST - before using any classes
require_once __DIR__ . '/../vendor/autoload.php';

// Enable all error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Initialize logger early
Logger::init(__DIR__ . '/../var/log');

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
