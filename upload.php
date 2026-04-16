<?php
require_once __DIR__ . '/app/bootstrap.php';
$controller = new UploadController($conn);
$controller->index();