<?php

require_once __DIR__ . '/Database.php';

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function authenticate($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function create($email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashedPassword);
        return $stmt->execute();
    }

    public function createWithEmail($name, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, verification_code, verification_expiry) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $verificationCode, $expiry);
        return $stmt->execute();
    }

    public function regenerateVerificationCode($email) {
        $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt = $this->db->prepare("UPDATE users SET verification_code = ?, verification_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $verificationCode, $expiry, $email);
        return $stmt->execute();
    }

    public function verifyEmail($verificationCode) {
        $stmt = $this->db->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expiry = NULL WHERE verification_code = ? AND verification_expiry > NOW()");
        $stmt->bind_param("s", $verificationCode);
        return $stmt->execute();
    }

    public function getByVerificationCode($verificationCode) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE verification_code = ? AND verification_expiry > NOW()");
        $stmt->bind_param("s", $verificationCode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function setGoogleEmailVerified($email) {
        $stmt = $this->db->prepare("UPDATE users SET email_verified = 1, verification_code = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        return $stmt->execute();
    }

    public function updateSessionId($userId, $sessionId) {
        $stmt = $this->db->prepare("UPDATE users SET session_id = ? WHERE id = ?");
        $stmt->bind_param("si", $sessionId, $userId);
        return $stmt->execute();
    }

    public function getBySessionId($sessionId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE session_id = ?");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function clearSessionId($userId) {
        $stmt = $this->db->prepare("UPDATE users SET session_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getStatus($id) {
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getByGoogleId($googleId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->bind_param("s", $googleId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function createGoogleUser($googleId, $email, $name, $picture) {
        $stmt = $this->db->prepare("INSERT INTO users (google_id, email, name, picture) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $googleId, $email, $name, $picture);
        return $stmt->execute();
    }

    public function updateGoogleUser($googleId, $email, $name, $picture) {
        $stmt = $this->db->prepare("UPDATE users SET email = ?, name = ?, picture = ? WHERE google_id = ?");
        $stmt->bind_param("ssss", $email, $name, $picture, $googleId);
        return $stmt->execute();
    }
}
