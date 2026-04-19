<?php

require_once __DIR__ . '/../Model/Instance.php';
require_once __DIR__ . '/../Model/UploadedFile.php';

class DashboardController {
    private $instanceModel;
    private $uploadedFileModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->instanceModel = new Instance();
        $this->uploadedFileModel = new UploadedFile();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $instances = $this->instanceModel->getByUserId($user['id']);
        $files = $this->uploadedFileModel->getByUserId($user['id']);

        require __DIR__ . '/../View/dashboard.php';
    }
}
