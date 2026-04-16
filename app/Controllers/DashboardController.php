<?php

class DashboardController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function index(): void
    {
        Auth::check($this->conn);

        $user   = Auth::user();
        $userId = Auth::userId();

        $uploadModel = new Upload($this->conn);
        $userModel   = new User($this->conn);

        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        // Handle delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isset($_POST['file_id'])) {
            $fileId   = (int)$_POST['file_id'];
            $fileInfo = $uploadModel->findByIdAndUser($fileId, $userId);

            if ($fileInfo) {
                $fullPath = BASE_PATH . '/' . $fileInfo['file_path'];
                if (file_exists($fullPath)) unlink($fullPath);
                $uploadModel->delete($fileId, $userId);
                ActivityLogger::log($this->conn, $userId, 'delete_file', 'Xóa file: ' . $fileInfo['file_name']);
                $_SESSION['message'] = 'Xóa file thành công.';
            }
            header("Location: dashboard.php");
            exit();
        }

        $stats          = $uploadModel->getStats($userId);
        $storageLimit   = $userModel->getStorageLimit($userId);
        $storagePercent = $storageLimit > 0 ? min(100, round(($stats['total_size'] / $storageLimit) * 100, 1)) : 0;
        $storageExceeded    = $stats['total_size'] >= $storageLimit;
        $storageNearlyFull  = $storagePercent >= 90 && !$storageExceeded;
        $recent_files       = $uploadModel->getRecentByUserId($userId, 5);

        view('dashboard/index', compact(
            'user', 'message', 'stats', 'storageLimit', 'storagePercent',
            'storageExceeded', 'storageNearlyFull', 'recent_files'
        ));
    }
}
