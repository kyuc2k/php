<?php

require_once __DIR__ . '/../Model/User.php';

class AdminController {
    private $userModel;

    public function __construct() {
        session_start();
        $this->userModel = new User();
    }

    public function index() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($username && $password) {
                $this->userModel->create($username, $password);
            }
        }
        require __DIR__ . '/../View/admin.php';
    }
}
