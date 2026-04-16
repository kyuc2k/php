<?php

class CvProfile
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function create(int $uploadId, int $userId, string $token, string $parsedJson, string $rawText = ''): bool
    {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO cv_profiles (upload_id, user_id, token, parsed_data, raw_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $uploadId, $userId, $token, $parsedJson, $rawText);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT cp.parsed_data, cp.raw_text, u.name AS owner_name, u.avatar AS owner_picture
             FROM cv_profiles cp
             JOIN users u ON u.id = cp.user_id
             WHERE cp.token = ?"
        );
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
