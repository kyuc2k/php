<?php

require_once __DIR__ . '/Database.php';

class Instance {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM instances WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $instances = [];
        while ($row = $result->fetch_assoc()) {
            $instances[] = $row;
        }
        return $instances;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM instances WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create($userId, $containerName, $port, $status = 'running') {
        $stmt = $this->db->prepare("INSERT INTO instances (user_id, container_name, port, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $userId, $containerName, $port, $status);
        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE instances SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function createSession($userId, $vmName, $token, $expiresAt) {
        $stmt = $this->db->prepare("INSERT INTO vm_sessions (user_id, vm_name, token, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $vmName, $token, $expiresAt);
        return $stmt->execute();
    }

    public function startContainer($containerName) {
        $output = shell_exec("docker start " . escapeshellarg($containerName));
        return $output;
    }

    public function stopContainer($containerName) {
        $output = shell_exec("docker stop " . escapeshellarg($containerName));
        return $output;
    }

    public function createContainer($name, $port, $userPath, $vncPassword = '112169') {
        $cmd = sprintf(
            "docker run -d --name %s -p %s:6001 -v /var/www/php/vms/%s:/app/game:ro -e VNC_PASSWORD=%s --memory=100m --cpus=1 micro-saas 2>&1",
            escapeshellarg($name),
            escapeshellarg($port),
            escapeshellarg($userPath),
            escapeshellarg($vncPassword)
        );
        return shell_exec($cmd);
    }
}
