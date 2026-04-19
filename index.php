<?php

require_once __DIR__ . '/app/Controller/AuthController.php';
require_once __DIR__ . '/app/Controller/DashboardController.php';
require_once __DIR__ . '/app/Controller/AdminController.php';
require_once __DIR__ . '/app/Controller/VMController.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$route = parse_url($requestUri, PHP_URL_PATH);
$route = str_replace('/php', '', $route);
$route = rtrim($route, '/');

switch ($route) {
    case '':
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
