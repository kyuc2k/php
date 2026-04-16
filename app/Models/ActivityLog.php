<?php

class ActivityLog
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function log(?int $userId, string $action, string $details = '', ?string $ip = null): void
    {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $stmt = $this->conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }

    public function countByUser(int $userId, string $actionFilter = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?";
        $params = [$userId];
        $types  = "i";

        if ($actionFilter !== '') {
            $sql .= " AND action = ?";
            $params[] = $actionFilter;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        return (int)$total;
    }

    public function getByUser(int $userId, string $actionFilter = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT action, details, ip_address, created_at FROM activity_logs WHERE user_id = ?";
        $params = [$userId];
        $types  = "i";

        if ($actionFilter !== '') {
            $sql .= " AND action = ?";
            $params[] = $actionFilter;
            $types .= "s";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getSummaryByUser(int $userId): array
    {
        $stmt = $this->conn->prepare("SELECT action, COUNT(*) as cnt FROM activity_logs WHERE user_id = ? GROUP BY action");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = [];
        while ($r = $result->fetch_assoc()) {
            $summary[$r['action']] = $r['cnt'];
        }
        $stmt->close();
        return $summary;
    }
}
