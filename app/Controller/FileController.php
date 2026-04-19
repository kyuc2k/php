<?php

require_once __DIR__ . '/../Model/UploadedFile.php';
require_once __DIR__ . '/../Model/UserLog.php';

class FileController {
    private $uploadedFileModel;
    private $userLog;

    public function __construct() {
        session_start();
        $this->uploadedFileModel = new UploadedFile();
        $this->userLog = new UserLog();
    }

    public function upload() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jar_file'])) {
            $userId = $_SESSION['user']['id'];
            $file = $_FILES['jar_file'];
            
            // Check if user already has 3 files
            $fileCount = $this->uploadedFileModel->countByUserId($userId);
            if ($fileCount >= 3) {
                $error = 'Bạn chỉ được upload tối đa 3 file.';
                $files = $this->uploadedFileModel->getByUserId($userId);
                require __DIR__ . '/../View/upload-file.php';
                return;
            }

            // Check file extension
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'jar') {
                $error = 'Chỉ cho phép upload file .jar';
                $files = $this->uploadedFileModel->getByUserId($userId);
                require __DIR__ . '/../View/upload-file.php';
                return;
            }

            // Check file size (10MB = 10 * 1024 * 1024 bytes)
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $error = 'File không được vượt quá 10MB';
                $files = $this->uploadedFileModel->getByUserId($userId);
                require __DIR__ . '/../View/upload-file.php';
                return;
            }

            // Check upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Có lỗi xảy ra khi upload file';
                $files = $this->uploadedFileModel->getByUserId($userId);
                require __DIR__ . '/../View/upload-file.php';
                return;
            }

            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/../../vms/user_' . $userId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename using original name with timestamp
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $basename = pathinfo($file['name'], PATHINFO_FILENAME);
            $sanitizedBasename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
            $filename = $sanitizedBasename . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Save to database
                $result = $this->uploadedFileModel->create(
                    $userId,
                    $filename,
                    $file['name'],
                    $file['size'],
                    $filePath
                );

                if ($result) {
                    $this->userLog->create($userId, 'FILE_UPLOAD', 'Uploaded file: ' . $file['name']);
                    header('Location: /upload-file?success=uploaded');
                    exit;
                } else {
                    $error = 'Lưu thông tin file thất bại';
                    // Delete uploaded file if database save failed
                    unlink($filePath);
                }
            } else {
                $error = 'Upload file thất bại';
            }
        }

        $files = $this->uploadedFileModel->getByUserId($_SESSION['user']['id']);
        require __DIR__ . '/../View/upload-file.php';
    }

    public function delete() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
            $fileId = $_POST['file_id'];
            $userId = $_SESSION['user']['id'];
            
            $file = $this->uploadedFileModel->getById($fileId);
            
            if (!$file) {
                $error = 'File không tồn tại';
            } elseif ($file['user_id'] != $userId) {
                $error = 'Bạn không có quyền xóa file này';
            } else {
                $result = $this->uploadedFileModel->delete($fileId);
                if ($result) {
                    $this->userLog->create($userId, 'FILE_DELETE', 'Deleted file: ' . $file['original_name']);
                    header('Location: /upload-file?success=deleted');
                    exit;
                } else {
                    $error = 'Xóa file thất bại';
                }
            }
        }

        $files = $this->uploadedFileModel->getByUserId($_SESSION['user']['id']);
        require __DIR__ . '/../View/upload-file.php';
    }
}
