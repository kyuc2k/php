<?php

class Upload
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM uploads WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $stmt = $this->conn->prepare("SELECT file_name, file_path, user_id FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getByUserId(int $userId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.file_name, u.file_path, u.file_size, u.uploaded_at, cp.token AS cv_token
             FROM uploads u LEFT JOIN cv_profiles cp ON cp.upload_id = u.id
             WHERE u.user_id = ? ORDER BY u.uploaded_at DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function getRecentByUserId(int $userId, int $limit = 5): array
    {
        $stmt = $this->conn->prepare(
            "SELECT u.id, u.file_name, u.file_path, u.file_size, u.uploaded_at, cp.token AS cv_token
             FROM uploads u LEFT JOIN cv_profiles cp ON cp.upload_id = u.id
             WHERE u.user_id = ? ORDER BY u.uploaded_at DESC LIMIT ?"
        );
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function getStats(int $userId): array
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total_files, COALESCE(SUM(file_size), 0) as total_size FROM uploads WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function getUsedStorage(int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(file_size), 0) as used FROM uploads WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)$row['used'];
    }

    public function nameExists(int $userId, string $fileName): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM uploads WHERE user_id = ? AND file_name = ?");
        $stmt->bind_param("is", $userId, $fileName);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function create(int $userId, string $fileName, string $filePath, int $fileSize): int
    {
        $stmt = $this->conn->prepare("INSERT INTO uploads (user_id, file_name, file_path, file_size) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $userId, $fileName, $filePath, $fileSize);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        return $affected;
    }
}
