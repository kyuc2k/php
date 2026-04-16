<?php

class Payment
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function create(array $data): int
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO payments (user_id, plan, amount, storage_bytes, order_id, request_id, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param(
            "isiiss",
            $data['user_id'], $data['plan'], $data['amount'],
            $data['storage_bytes'], $data['order_id'], $data['request_id']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function findByOrderId(string $orderId): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, user_id, plan, amount, storage_bytes, status, transaction_id FROM payments WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByOrderIdAndUser(string $orderId, int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, status, transaction_id, plan, storage_bytes FROM payments WHERE order_id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("si", $orderId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function markCompleted(int $id, string $transactionId): void
    {
        $stmt = $this->conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $transactionId, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function markCompletedIfPending(int $id, string $transactionId): void
    {
        $stmt = $this->conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ? AND status != 'completed'");
        $stmt->bind_param("si", $transactionId, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function getPaidPlansByUser(int $userId): array
    {
        $stmt = $this->conn->prepare("SELECT plan FROM payments WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $plans = [];
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row['plan'];
        }
        $stmt->close();
        return $plans;
    }
}
