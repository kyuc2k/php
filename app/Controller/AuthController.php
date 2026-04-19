<?php

require_once __DIR__ . '/../Model/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        session_start();
        $this->userModel = new User();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->authenticate($username, $password);

            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: /dashboard');
                exit;
            } else {
                $error = 'Invalid username or password';
                require __DIR__ . '/../View/login.php';
                return;
            }
        }
        require __DIR__ . '/../View/login.php';
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
