<?php

class User
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND password IS NOT NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByGoogleIdOrEmail(string $googleId, string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, email_verified, google_id FROM users WHERE google_id = ? OR email = ?");
        $stmt->bind_param("ss", $googleId, $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function findByEmailAny(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        if (isset($data['google_id'])) {
            $stmt = $this->conn->prepare("INSERT INTO users (google_id, name, email, avatar, email_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $data['google_id'], $data['name'], $data['email'], $data['avatar']);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, verification_code, email_verified, verification_code_expires) VALUES (?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
            $stmt->bind_param("ssss", $data['name'], $data['email'], $data['password'], $data['verification_code']);
        }
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function updateSessionToken(int $userId, ?string $token): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET session_token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function getSessionToken(int $userId): ?string
    {
        $stmt = $this->conn->prepare("SELECT session_token FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['session_token'] ?? null;
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function updatePasswordByEmail(string $email, string $hash): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET password = ?, verification_code = NULL, verification_code_expires = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();
        $stmt->close();
    }

    public function updateVerificationCode(string $email, string $code, int $minutes = 5): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE email = ?");
        $stmt->bind_param("sis", $code, $minutes, $email);
        $stmt->execute();
        $stmt->close();
    }

    public function verifyEmail(string $email): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    public function verifyCode(string $email, string $code): ?array
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND verification_code_expires > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function linkGoogle(int $userId, string $googleId, string $name, string $avatar): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET google_id = ?, name = ?, avatar = ?, email_verified = 1 WHERE id = ?");
        $stmt->bind_param("sssi", $googleId, $name, $avatar, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function updateStorageLimit(int $userId, int $bytes): void
    {
        $stmt = $this->conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
        $stmt->bind_param("ii", $bytes, $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function getStorageLimit(int $userId): int
    {
        $stmt = $this->conn->prepare("SELECT storage_limit FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['storage_limit'] ?? 10485760;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function ensureSessionTokenColumn(): void
    {
        $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'session_token'");
        if ($result->num_rows == 0) {
            $this->conn->query("ALTER TABLE users ADD session_token VARCHAR(64) DEFAULT NULL");
        }
    }

    public function findByEmailWithPassword(string $email): ?array
    {
        $stmt = $this->conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
