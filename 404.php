<?php
require_once __DIR__ . '/app/bootstrap.php';
$controller = new HomeController($conn);
$controller->notFound();