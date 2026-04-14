<?php
session_start();
require 'config.php';

require 'auth_check.php';

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Lấy message từ session nếu có (sau redirect)
$message = $_SESSION['upload_message'] ?? '';
unset($_SESSION['upload_message']);

// Thư mục lưu file upload
$uploadDir = __DIR__ . '/uploads/';

$uploadAvailable = true;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $uploadAvailable = false;
    }
}

if ($uploadAvailable && !is_writable($uploadDir)) {
    @chmod($uploadDir, 0755);
    clearstatcache(true, $uploadDir);
    if (!is_writable($uploadDir)) {
        $uploadAvailable = false;
    }
}

// Tạo bảng uploads nếu chưa tồn tại
$conn->query(
    "CREATE TABLE IF NOT EXISTS uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['file_id'])) {
    $fileId = (int)$_POST['file_id'];

    // Lấy thông tin file để kiểm tra quyền và đường dẫn
    $stmt = $conn->prepare("SELECT file_name, file_path, user_id FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $fileInfo = $result->fetch_assoc();
    $stmt->close();

    if ($fileInfo && $fileInfo['user_id'] == $userId) {
        // Xóa file khỏi hệ thống
        $fullPath = __DIR__ . '/' . $fileInfo['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Xóa khỏi database
        $stmt = $conn->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $fileId, $userId);
        $stmt->execute();
        $stmt->close();

        $_SESSION['upload_message'] = 'Xóa file thành công.';
        header("Location: upload.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];

    $uploadError = null;

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $uploadError = 'File quá lớn (tối đa ' . ini_get('upload_max_filesize') . ').';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $uploadError = 'File quá lớn theo giới hạn form.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $uploadError = 'File upload không hoàn toàn (lỗi kết nối).';
                break;
            case UPLOAD_ERR_NO_FILE:
                $uploadError = 'Không có file nào được chọn.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $uploadError = 'Thư mục temp không tồn tại.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $uploadError = 'Không thể ghi file vào server.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $uploadError = 'File upload bị chặn bởi extension PHP.';
                break;
            default:
                $uploadError = 'Lỗi upload không xác định: ' . $file['error'];
        }
    } elseif ($file['type'] !== 'application/pdf') {
        $uploadError = 'Chỉ chấp nhận file PDF.';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB
        $uploadError = 'File quá lớn (tối đa 10MB).';
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        $uploadError = 'Tệp tải lên không hợp lệ.';
    } elseif (!$uploadAvailable) {
        $uploadError = 'Upload thất bại vì thư mục lưu không khả dụng.';
    } elseif (!$userId) {
        $uploadError = 'Không xác định được người dùng hiện tại.';
    } else {
        // Tạo tên file duy nhất với timestamp
        $timestamp = date('dmYHis'); // ngày tháng năm giờ phút giây
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME); // Lấy tên file không có extension
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION); // Lấy extension
        $fileName = $timestamp . '_' . $originalName . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        $relativePath = 'uploads/' . $fileName;  // Đường dẫn tương đối để lưu vào DB

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $stmt = $conn->prepare("INSERT INTO uploads (user_id, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $fileName, $relativePath);
            $stmt->execute();
            $stmt->close();

            $_SESSION['upload_message'] = 'Upload thành công: <a href="' . htmlspecialchars($relativePath) . '" target="_blank">' . htmlspecialchars($fileName) . '</a>';
            
            // Redirect để tránh duplicate upload khi reload
            header("Location: upload.php");
            exit();
        } else {
            $error = error_get_last();
            $uploadError = 'Lỗi lưu file.' . ($error ? ' ' . $error['message'] : '');
        }
    }

    if ($uploadError) {
        $message = $uploadError;
    }
}

// Lấy danh sách file của user hiện tại
$uploads = [];
if ($userId) {
    $stmt = $conn->prepare("SELECT id, file_name, file_path, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uploads[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF - <?= htmlspecialchars($user['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        .upload-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            overflow: hidden;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            min-width: 0;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title i {
            font-size: 2rem;
            color: #667eea;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            min-width: 0;
        }

        .upload-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-width: 0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
            position: relative;
            max-width: 100%;
            overflow: hidden;
        }

        .upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .upload-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .upload-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 10px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .upload-subtext {
            font-size: 0.9rem;
            color: #999;
            margin-bottom: 25px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .file-input {
            display: none;
        }

        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            max-width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            white-space: normal;
            flex-wrap: wrap;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .files-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-width: 0;
            overflow: hidden;
        }

        .files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .file-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        .file-grid {
            display: grid;
            gap: 15px;
            max-height: 500px;
            overflow-y: auto;
            padding: 5px;
        }

        .file-grid::-webkit-scrollbar {
            width: 8px;
        }

        .file-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .file-grid::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .file-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .file-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 20px;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .file-info {
            flex: 1;
            min-width: 0;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            word-break: break-all;
            overflow-wrap: anywhere;
        }

        .file-date {
            color: #666;
            font-size: 0.9rem;
        }

        .file-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .file-action {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: none;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .file-action.delete:hover {
            background: #dc3545;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            text-align: center;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fee;
            color: #dc3545;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-message {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 25px;
        }

        .modal-file-name {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            color: #667eea;
            word-break: break-all;
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .modal-btn-cancel {
            background: #e0e0e0;
            color: #666;
        }

        .modal-btn-cancel:hover {
            background: #d0d0d0;
        }

        .modal-btn-confirm {
            background: #dc3545;
            color: white;
        }

        .modal-btn-confirm:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .message a {
            color: inherit;
            text-decoration: underline;
            max-width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .message-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }

        .message-success {
            background: #efe;
            color: #060;
            border: 1px solid #cfc;
        }

        .message i {
            font-size: 1.2rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            font-size: 1rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .upload-container {
                padding: 15px;
            }

            .main-content {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .file-stats {
                flex-direction: column;
            }

            .upload-area {
                padding: 30px 20px;
            }

            .upload-icon {
                font-size: 3rem;
            }
        }

        @media (max-width: 480px) {
            .upload-container {
                padding: 10px;
            }

            .header-title h1 {
                font-size: 1.5rem;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .upload-area {
                padding: 20px 15px;
            }

            .upload-btn {
                width: 100%;
                max-width: 100%;
                padding: 12px 16px;
                font-size: 1rem;
            }

            .message {
                display: block;
            }

            .message i {
                margin-right: 10px;
            }

            .upload-icon {
                font-size: 2.5rem;
            }

            .file-item {
                padding: 15px;
            }

            .file-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .file-action {
                width: 35px;
                height: 35px;
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 20px;
        }

        .loading-overlay.show {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .loading-text {
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Đang tải lên...</div>
    </div>
    <div class="upload-container">
        <header>
            <div class="header-title">
                <i class="fas fa-cloud-upload-alt"></i>
                <h1>Upload PDF</h1>
            </div>
            
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                    Đăng xuất
                </a>
            </div>
        </header>

        <div class="main-content">
            <div class="upload-section">
                <h2 class="section-title">
                    <i class="fas fa-file-upload"></i>
                    Tải lên file mới
                </h2>

                <?php if ($message): ?>
                    <div class="message <?php echo (strpos($message, 'thành công') !== false) ? 'message-success' : 'message-error'; ?>">
                        <i class="fas <?php echo (strpos($message, 'thành công') !== false) ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($uploadAvailable): ?>
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-text">Kéo và thả file PDF vào đây</div>
                        <div class="upload-subtext">hoặc click để chọn file</div>
                        <form method="post" enctype="multipart/form-data" id="uploadForm">
                            <input type="file" name="pdf_file" accept=".pdf" id="fileInput" class="file-input" required>
                            <button type="submit" class="upload-btn">
                                <i class="fas fa-upload"></i>
                                Chọn file PDF
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="message message-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        Upload tạm dừng vì thư mục lưu không khả dụng. Vui lòng tạo thủ công thư mục <strong>uploads</strong> và cấp quyền ghi.
                    </div>
                <?php endif; ?>
            </div>

            <div class="files-section">
                <div class="files-header">
                    <h2 class="section-title">
                        <i class="fas fa-folder-open"></i>
                        File đã tải lên
                    </h2>
                </div>

                <div class="file-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= count($uploads) ?></div>
                        <div class="stat-label">Tổng file</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= count(array_filter($uploads, function($f) { return strtotime($f['uploaded_at']) > strtotime('-7 days'); })) ?></div>
                        <div class="stat-label">7 ngày qua</div>
                    </div>
                </div>

                <?php if (empty($uploads)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Chưa có file nào</h3>
                        <p>Bắt đầu tải lên file PDF đầu tiên của bạn!</p>
                    </div>
                <?php else: ?>
                    <div class="file-grid">
                        <?php foreach ($uploads as $upload): ?>
                            <div class="file-item">
                                <div class="file-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name" title="<?= htmlspecialchars($upload['file_name']) ?>">
                                        <?= htmlspecialchars($upload['file_name']) ?>
                                    </div>
                                    <div class="file-date">
                                        <i class="far fa-clock"></i>
                                        <?= date('d/m/Y H:i', strtotime($upload['uploaded_at'])) ?>
                                    </div>
                                </div>
                                <div class="file-actions">
                                    <a href="<?= htmlspecialchars($upload['file_path']) ?>" target="_blank" class="file-action" title="Xem file">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="file-action delete" title="Xóa file" onclick="showDeleteModal(<?= htmlspecialchars($upload['id']) ?>, '<?= htmlspecialchars($upload['file_name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title">Xác nhận xóa file</h3>
            </div>
            <div class="modal-message">
                Bạn có chắc chắn muốn xóa file này không?
                <div class="modal-file-name" id="modalFileName"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                    Hủy
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i>
                    Xóa
                </button>
            </div>
        </div>
    </div>

    <!-- Session Kicked Modal -->
    <div id="sessionKickedModal" class="modal">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-icon" style="background: linear-gradient(135deg, #ff9800 0%, #f44336 100%); color: white;">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3 class="modal-title">Phiên đăng nhập đã kết thúc</h3>
            <div class="modal-message">
                Tài khoản của bạn vừa được đăng nhập trên một thiết bị khác. Vì lý do bảo mật, phiên hiện tại sẽ bị đăng xuất.
            </div>
            <div id="sessionKickedCountdown" style="font-size: 0.9rem; color: #999; margin-bottom: 20px;">
                Tự động chuyển hướng sau <span id="countdownTimer">5</span> giây...
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%;" onclick="window.location.href='login.php'">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập lại
                </button>
            </div>
        </div>
    </div>

    <script>
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const uploadForm = document.getElementById('uploadForm');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        function showUploadLoading() {
            document.getElementById('loadingOverlay').classList.add('show');
            const submitBtn = uploadForm.querySelector('.upload-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
            submitBtn.disabled = true;
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                showUploadLoading();
                uploadForm.submit();
            }
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        // Click to upload
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                if (fileName.toLowerCase().endsWith('.pdf')) {
                    showUploadLoading();
                    uploadForm.submit();
                } else {
                    alert('Vui lòng chọn file PDF!');
                }
            }
        });

        // Modal functionality
        let currentFileId = null;

        function showDeleteModal(fileId, fileName) {
            currentFileId = fileId;
            const modal = document.getElementById('deleteModal');
            const modalFileName = document.getElementById('modalFileName');
            
            modalFileName.textContent = fileName;
            modal.classList.add('show');
            
            // Focus on confirm button
            document.getElementById('confirmDeleteBtn').focus();
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('show');
            currentFileId = null;
        }

        // Confirm delete button
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (currentFileId) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const fileIdInput = document.createElement('input');
                fileIdInput.type = 'hidden';
                fileIdInput.name = 'file_id';
                fileIdInput.value = currentFileId;
                form.appendChild(fileIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Close modal on background click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Real-time session check - polls every 3 seconds
        let sessionKicked = false;
        setInterval(function() {
            if (sessionKicked) return;
            fetch('check_session.php')
                .then(r => r.json())
                .then(data => {
                    if (!data.valid && !sessionKicked) {
                        sessionKicked = true;
                        document.getElementById('sessionKickedModal').classList.add('show');
                        let seconds = 5;
                        const timer = setInterval(function() {
                            seconds--;
                            document.getElementById('countdownTimer').textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(timer);
                                window.location.href = 'login.php';
                            }
                        }, 1000);
                    }
                })
                .catch(() => {});
        }, 3000);
    </script>
</body>
</html>