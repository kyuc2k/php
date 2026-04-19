<?php

// Enable error reporting for debugging
// Test github action
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/Controller/AuthController.php';
require_once __DIR__ . '/app/Controller/DashboardController.php';
require_once __DIR__ . '/app/Controller/AdminController.php';
require_once __DIR__ . '/app/Controller/VMController.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$route = parse_url($requestUri, PHP_URL_PATH);

// Handle both localhost (/php/php/...) and VPS (/...) paths
if (strpos($route, '/php/php') === 0) {
    $route = substr($route, 8); // Remove /php/php
} elseif (strpos($route, '/php') === 0) {
    $route = substr($route, 4); // Remove /php
}

$route = rtrim($route, '/');
if (empty($route)) {
    $route = '/';
}

// Debug: Log routing info (comment out in production)
// error_log("Route: $route, URI: $requestUri");

switch ($route) {
    case '':
    case '/':
        require __DIR__ . '/app/View/home.php';
        break;

    case '/login':
        $controller = new AuthController();
        $controller->login();
        break;

    case '/logout':
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;

    case '/admin':
        $controller = new AdminController();
        $controller->index();
        break;

    case '/vm/create':
        $controller = new VMController();
        $controller->create();
        break;

    case '/vm/start':
        $controller = new VMController();
        $controller->start();
        break;

    case '/vm/stop':
        $controller = new VMController();
        $controller->stop();
        break;

    case '/vm/status':
        $controller = new VMController();
        $controller->status();
        break;

    default:
        http_response_code(404);
        echo '404 - Page not found';
        break;
}
