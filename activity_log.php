<?php
require_once __DIR__ . '/app/bootstrap.php';
$controller = new ActivityController($conn);
$controller->index();