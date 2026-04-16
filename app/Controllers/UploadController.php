<?php

class UploadController
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

        $message = $_SESSION['upload_message'] ?? '';
        unset($_SESSION['upload_message']);

        // Upload directory setup
        $uploadDir = BASE_PATH . '/uploads/';
        $uploadAvailable = true;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                $uploadAvailable = false;
            }
        }
        if ($uploadAvailable && !is_writable($uploadDir)) {
            @chmod($uploadDir, 0755);
            clearstatcache(true, $uploadDir);
            if (!is_writable($uploadDir)) $uploadAvailable = false;
        }

        $storageLimit = $userModel->getStorageLimit($userId);
        $usedStorage  = $uploadModel->getUsedStorage($userId);
        $storagePercent  = $storageLimit > 0 ? min(100, round(($usedStorage / $storageLimit) * 100, 1)) : 0;
        $storageExceeded = $usedStorage >= $storageLimit;
        $storageNearlyFull = $storagePercent >= 90 && !$storageExceeded;

        $showCustomNameForm = false;
        $originalFileName   = '';
        $tempFileToken      = '';
        $uploadError        = null;

        // Handle delete
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isset($_POST['file_id'])) {
            $fileId   = (int)$_POST['file_id'];
            $fileInfo = $uploadModel->findByIdAndUser($fileId, $userId);
            if ($fileInfo) {
                $fullPath = BASE_PATH . '/' . $fileInfo['file_path'];
                if (file_exists($fullPath)) unlink($fullPath);
                $uploadModel->delete($fileId, $userId);
                ActivityLogger::log($this->conn, $userId, 'delete_file', 'Xóa file: ' . $fileInfo['file_name']);
                $_SESSION['upload_message'] = 'Xóa file thành công.';
                header("Location: upload.php");
                exit();
            }
        }

        // Handle upload from temp (custom filename on duplicate)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['temp_token']) && isset($_POST['custom_filename'])) {
            $tempToken = $_POST['temp_token'];
            $tempPath  = BASE_PATH . '/uploads/temp/' . $tempToken . '.pdf';

            if (!file_exists($tempPath)) {
                $message = 'File tạm đã hết hạn. Vui lòng upload lại.';
            } else {
                $fileName  = trim($_POST['custom_filename']);
                $extension = 'pdf';
                if (pathinfo($fileName, PATHINFO_EXTENSION) !== $extension) {
                    $fileName .= '.' . $extension;
                }

                if ($uploadModel->nameExists($userId, $fileName)) {
                    $uploadError = 'File "' . htmlspecialchars($fileName) . '" đã tồn tại. Vui lòng nhập tên khác:';
                    $showCustomNameForm = true;
                    $originalFileName   = $fileName;
                    $tempFileToken      = $tempToken;
                } else {
                    $filePath     = $uploadDir . $fileName;
                    $relativePath = 'uploads/' . $fileName;

                    // Check harmful content before moving file from temp
                    $harmfulCheck = cv_checkHarmfulContent($tempPath);
                    if ($harmfulCheck['is_harmful']) {
                        $message = 'File chứa nội dung không phù hợp: ' . htmlspecialchars($harmfulCheck['reason']) . '. Upload bị từ chối.';
                        if (file_exists($tempPath)) unlink($tempPath);
                    } elseif (rename($tempPath, $filePath)) {
                        $fileSize    = filesize($filePath);
                        $newUploadId = $uploadModel->create($userId, $fileName, $relativePath, $fileSize);
                        if (file_exists($tempPath)) unlink($tempPath);

                        $this->handleCvDetection($filePath, $fileName, $newUploadId, $userId, $relativePath);
                        ActivityLogger::log($this->conn, $userId, 'upload_file', 'Upload file: ' . $fileName);
                        header("Location: upload.php");
                        exit();
                    } else {
                        $message = 'Lỗi di chuyển file.';
                    }
                }
            }
        }

        // Handle new file upload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
            error_log("Upload POST received: custom_filename=" . ($_POST['custom_filename'] ?? 'null') . ", file=" . $_FILES['pdf_file']['name']);
            $file = $_FILES['pdf_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE   => 'File quá lớn (tối đa ' . ini_get('upload_max_filesize') . ').',
                    UPLOAD_ERR_FORM_SIZE  => 'File quá lớn theo giới hạn form.',
                    UPLOAD_ERR_PARTIAL    => 'File upload không hoàn toàn (lỗi kết nối).',
                    UPLOAD_ERR_NO_FILE    => 'Không có file nào được chọn.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Thư mục temp không tồn tại.',
                    UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào server.',
                    UPLOAD_ERR_EXTENSION  => 'File upload bị chặn bởi extension PHP.',
                ];
                $uploadError = $errorMessages[$file['error']] ?? 'Lỗi upload không xác định: ' . $file['error'];
            } elseif ($file['type'] !== 'application/pdf') {
                $uploadError = 'Chỉ chấp nhận file PDF.';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $uploadError = 'File quá lớn (tối đa 10MB).';
            } elseif (($usedStorage + $file['size']) > $storageLimit) {
                $uploadError = 'Hết dung lượng lưu trữ! Bạn đã dùng ' . number_format($usedStorage / 1024 / 1024, 2) . 'MB / ' . number_format($storageLimit / 1024 / 1024, 0) . 'MB. Vui lòng <a href="upgrade.php" style="color:#667eea;font-weight:600;">nâng cấp</a> hoặc xóa bớt file để tiếp tục upload.';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $uploadError = 'Tệp tải lên không hợp lệ.';
            } elseif (!$uploadAvailable) {
                $uploadError = 'Upload thất bại vì thư mục lưu không khả dụng.';
            } elseif (!$userId) {
                $uploadError = 'Không xác định được người dùng hiện tại.';
            } else {
                $fileName  = $file['name'];
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $exists    = $uploadModel->nameExists($userId, $fileName);

                if (isset($_POST['custom_filename']) && trim($_POST['custom_filename']) !== '') {
                    $customName = trim($_POST['custom_filename']);
                    if (pathinfo($customName, PATHINFO_EXTENSION) !== $extension) {
                        $customName .= '.' . $extension;
                    }
                    $fileName = $customName;
                    $exists   = $uploadModel->nameExists($userId, $fileName);
                }

                if ($exists) {
                    $tempDir = BASE_PATH . '/uploads/temp/';
                    if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
                    $tempToken = bin2hex(random_bytes(16));
                    $tempPath  = $tempDir . $tempToken . '.pdf';

                    if (move_uploaded_file($file['tmp_name'], $tempPath)) {
                        $uploadError = 'File "' . htmlspecialchars($fileName) . '" đã tồn tại. Vui lòng nhập tên khác bên dưới:';
                        $showCustomNameForm = true;
                        $originalFileName   = $file['name'];
                        $tempFileToken      = $tempToken;
                    } else {
                        $uploadError = 'Lỗi lưu file tạm.';
                    }
                } else {
                    $showCustomNameForm = false;
                    $filePath     = $uploadDir . $fileName;
                    $relativePath = 'uploads/' . $fileName;

                    // Check harmful content before saving file
                    $harmfulCheck = cv_checkHarmfulContent($file['tmp_name']);
                    if ($harmfulCheck['is_harmful']) {
                        $uploadError = 'File chứa nội dung không phù hợp: ' . htmlspecialchars($harmfulCheck['reason']) . '. Upload bị từ chối.';
                    } elseif (move_uploaded_file($file['tmp_name'], $filePath)) {
                        $fileSize    = filesize($filePath);
                        $newUploadId = $uploadModel->create($userId, $fileName, $relativePath, $fileSize);

                        $this->handleCvDetection($filePath, $file['name'], $newUploadId, $userId, $relativePath);
                        ActivityLogger::log($this->conn, $userId, 'upload_file', 'Upload file: ' . $fileName);
                        header("Location: upload.php");
                        exit();
                    } else {
                        $error = error_get_last();
                        $uploadError = 'Lỗi lưu file.' . ($error ? ' ' . $error['message'] : '');
                    }
                }
            }

            if ($uploadError) $message = $uploadError;
        }

        $uploads = $uploadModel->getByUserId($userId);

        view('upload/index', compact(
            'user', 'message', 'uploads', 'storageLimit', 'usedStorage',
            'storagePercent', 'storageExceeded', 'storageNearlyFull',
            'showCustomNameForm', 'originalFileName', 'tempFileToken', 'uploadAvailable', 'uploadError'
        ));
    }

    private function handleCvDetection(string $filePath, string $originalName, int $uploadId, int $userId, string $relativePath): void
    {
        $isCV = preg_match('/(?<![a-zA-Z])cv(?![a-zA-Z])/i', pathinfo($originalName, PATHINFO_FILENAME));
        if (!$isCV) {
            $_SESSION['upload_message'] = 'Upload thành công: <a href="' . htmlspecialchars($relativePath) . '" target="_blank">' . htmlspecialchars(basename($filePath)) . '</a>';
            return;
        }

        $cvData   = CvParser::parse($filePath);
        
        // Check if Gemini returned an error
        if (isset($cvData['error'])) {
            // Don't save to DB, just show upload success without CV view
            $_SESSION['upload_message'] = 'Upload thành công: <a href="' . htmlspecialchars($relativePath) . '" target="_blank">' . htmlspecialchars(basename($filePath)) . '</a> (Lỗi phân tích CV: ' . htmlspecialchars($cvData['error']) . ')';
            return;
        }
        
        $token    = bin2hex(random_bytes(24));
        $photoDir = BASE_PATH . '/uploads/cv_photos/';
        if (!is_dir($photoDir)) mkdir($photoDir, 0755, true);
        CvParser::extractPhoto($filePath, $photoDir . $token . '.jpg');

        $json = json_encode($cvData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) $json = '{}';

        $cvModel = new CvProfile($this->conn);
        $cvModel->create($uploadId, $userId, $token, $json, '');

        $_SESSION['upload_message'] = 'Upload thành công! CV được phát hiện — <a href="cv_view.php?token=' . urlencode($token) . '" target="_blank" style="color:#667eea;font-weight:600;">Xem CV online</a>';
    }
}
