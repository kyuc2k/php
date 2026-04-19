<?php

require_once __DIR__ . '/../Model/Instance.php';

class DashboardController {
    private $instanceModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->instanceModel = new Instance();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $instances = $this->instanceModel->getByUserId($user['id']);

        require __DIR__ . '/../View/dashboard.php';
    }
}
