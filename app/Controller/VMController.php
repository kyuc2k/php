<?php

require_once __DIR__ . '/../Model/Instance.php';

class VMController {
    private $instanceModel;

    public function __construct() {
        session_start();
        $this->instanceModel = new Instance();
    }

    public function create() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $userId = (int)$user['id'];
        $time = time();
        $port = rand(6001, 7000);
        $name = "vm_{$userId}_{$time}";

        $basePath = "/var/www/php/vms/" . $userId;

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $userPath = $userId;
        $vnc_password = '112169';

        $output = $this->instanceModel->createContainer($name, $port, $userPath, $vnc_password);

        echo "<pre>";
        echo "OUTPUT:\n";
        var_dump($output);
        echo "</pre>";

        if ($output && !str_contains($output, 'error') && !str_contains($output, 'Error')) {
            $this->instanceModel->create($userId, $name, $port, 'running');

            $token = bin2hex(random_bytes(16));
            $expire = date("Y-m-d H:i:s", time() + 3600);

            $this->instanceModel->createSession($userId, $name, $token, $expire);
            echo "Docker create successful!";
        } else {
            echo "Docker create failed";
        }
    }

    public function start() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? 0;
        $vm = $this->instanceModel->getById($id);

        if ($vm) {
            $this->instanceModel->startContainer($vm['container_name']);
            $this->instanceModel->updateStatus($id, 'running');
        }

        header('Location: /dashboard');
        exit;
    }

    public function stop() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $id = $_GET['id'] ?? 0;
        $vm = $this->instanceModel->getById($id);

        if ($vm) {
            $this->instanceModel->stopContainer($vm['container_name']);
            $this->instanceModel->updateStatus($id, 'stopped');
        }

        header('Location: /dashboard');
        exit;
    }

    public function status() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $user = $_SESSION['user'];
        $userModel = new User();
        $status = $userModel->getStatus($user['id']);

        header('Content-Type: application/json');
        echo json_encode($status);
    }
}
