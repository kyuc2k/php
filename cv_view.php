<?php
require_once __DIR__ . '/app/bootstrap.php';
$controller = new CvController($conn);
$controller->view();