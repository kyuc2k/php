<?php

class UploadedFile {
    private $db;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $this->db = Database::getInstance();
    }

    public function create($userId, $filename, $originalName, $fileSize, $filePath) {
        $stmt = $this->db->prepare("INSERT INTO uploaded_files (user_id, filename, original_name, file_size, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issis", $userId, $filename, $originalName, $fileSize, $filePath);
        return $stmt->execute();
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM uploaded_files WHERE user_id = ? ORDER BY uploaded_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $files[] = $row;
        }
        return $files;
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM uploaded_files WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function delete($id) {
        $file = $this->getById($id);
        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        $stmt = $this->db->prepare("DELETE FROM uploaded_files WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function countByUserId($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM uploaded_files WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
