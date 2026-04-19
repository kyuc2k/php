<?php

class UserLog {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance();
    }

    public function create($userId, $action, $description = '', $ipAddress = null) {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        $stmt = $this->db->prepare("INSERT INTO user_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $action, $description, $ipAddress);
        return $stmt->execute();
    }

    public function getByUserId($userId, $limit = 50, $offset = 0, $action = null, $sort = 'created_at', $order = 'DESC') {
        $sql = "SELECT * FROM user_logs WHERE user_id = ?";
        $params = [$userId];
        $types = "i";

        if ($action) {
            $sql .= " AND action = ?";
            $params[] = $action;
            $types .= "s";
        }

        // Validate sort column
        $validSorts = ['id', 'action', 'description', 'ip_address', 'created_at'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'created_at';
        }

        // Validate order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }

    public function countByUserId($userId, $action = null) {
        $sql = "SELECT COUNT(*) as total FROM user_logs WHERE user_id = ?";
        $params = [$userId];
        $types = "i";

        if ($action) {
            $sql .= " AND action = ?";
            $params[] = $action;
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    public function getDistinctActions($userId) {
        $stmt = $this->db->prepare("SELECT DISTINCT action FROM user_logs WHERE user_id = ? ORDER BY action ASC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $actions = [];
        while ($row = $result->fetch_assoc()) {
            $actions[] = $row['action'];
        }
        return $actions;
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
