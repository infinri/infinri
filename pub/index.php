<?php
/**
 * Application Entry Point
 * 
 * This file is the main entry point for all HTTP requests.
 */

declare(strict_types=1);

use Infinri\Core\App\Request;

// Bootstrap the application
$frontController = require __DIR__ . '/../app/bootstrap.php';
$frontController = initApplication();

// Create request from globals
$request = Request::createFromGlobals();

// Dispatch request to controller
$response = $frontController->dispatch($request);

// Send response to browser
$response->send();
