<?php

require_once __DIR__ . '/../Model/Instance.php';
require_once __DIR__ . '/../Model/UploadedFile.php';
require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/Rental.php';

class DashboardController {
    private $instanceModel;
    private $uploadedFileModel;
    private $userModel;
    private $rentalModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->instanceModel = new Instance();
        $this->uploadedFileModel = new UploadedFile();
        $this->userModel = new User();
        $this->rentalModel = new Rental();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $instances = $this->instanceModel->getByUserId($user['id']);
        $files = $this->uploadedFileModel->getByUserId($user['id']);
        $balance = $this->userModel->getBalance($user['id']);
        $rentals = $this->rentalModel->getByUserId($user['id']);

        require __DIR__ . '/../View/dashboard.php';
    }
}
