<?php
require_once __DIR__ . '/app/bootstrap.php';
$controller = new AuthController($conn);
$controller->forgotPassword();