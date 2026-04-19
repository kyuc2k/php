<?php

class UserLog {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = new Database();
    }

    public function create($userId, $action, $description = '', $ipAddress = null) {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        $stmt = $this->db->prepare("INSERT INTO user_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $action, $description, $ipAddress);
        return $stmt->execute();
    }

    public function getByUserId($userId, $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }

    public function getAll($limit = 100) {
        $stmt = $this->db->prepare("SELECT user_logs.*, users.name, users.email FROM user_logs LEFT JOIN users ON user_logs.user_id = users.id ORDER BY user_logs.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }
}
