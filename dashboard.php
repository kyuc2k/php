<?php
require_once __DIR__ . "/app/bootstrap.php";
$controller = new DashboardController($conn);
$controller->index();
