<?php
session_start();
require 'config.php';

// Kiểm tra nếu chưa login thì chuyển hướng sang login.php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Thư mục lưu file upload
$uploadDir = __DIR__ . '/uploads/';

$message = '';
$uploadAvailable = true;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $message = 'Thư mục uploads không tồn tại và không thể tạo. Vui lòng tạo thủ công hoặc cấp quyền ghi cho thư mục.';
        $uploadAvailable = false;
    }
}

if ($uploadAvailable && !is_writable($uploadDir)) {
    @chmod($uploadDir, 0755);
    clearstatcache(true, $uploadDir);
    if (!is_writable($uploadDir)) {
        $message = 'Thư mục uploads không có quyền ghi. Vui lòng kiểm tra quyền của thư mục.';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Lỗi upload file: ' . $file['error'];
    } elseif ($file['type'] !== 'application/pdf') {
        $message = 'Chỉ chấp nhận file PDF.';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB
        $message = 'File quá lớn (tối đa 10MB).';
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        $message = 'Tệp tải lên không hợp lệ.';
    } elseif (!$uploadAvailable) {
        $message = 'Upload thất bại vì thư mục lưu không khả dụng.';
    } elseif (!$userId) {
        $message = 'Không xác định được người dùng hiện tại.';
    } else {
        // Tạo tên file duy nhất
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $stmt = $conn->prepare("INSERT INTO uploads (user_id, file_name, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $fileName, $filePath);
            $stmt->execute();
            $stmt->close();

            $message = 'Upload thành công: <a href="uploads/' . $fileName . '" target="_blank">' . $fileName . '</a>';
        } else {
            $error = error_get_last();
            $message = 'Lỗi lưu file.' . ($error ? ' ' . $error['message'] : '');
        }
    }
}

// Lấy danh sách file của user hiện tại
$uploads = [];
if ($userId) {
    $stmt = $conn->prepare("SELECT file_name, file_path, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC");
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
    <title>Upload PDF</title>
    <style>
        table { border-collapse: collapse; width: 100%; max-width: 800px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .notice { margin: 16px 0; color: #c00; }
    </style>
</head>
<body>
    <h1>Upload File PDF</h1>

    <?php if ($message): ?>
        <p class="notice"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if ($uploadAvailable): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="pdf_file" accept=".pdf" required>
            <button type="submit">Upload</button>
        </form>
    <?php else: ?>
        <p>Upload tạm dừng vì thư mục lưu không khả dụng. Vui lòng tạo thủ công thư mục <strong>uploads</strong> và cấp quyền ghi.</p>
    <?php endif; ?>

    <h2>File PDF bạn đã tải lên</h2>

    <?php if (empty($uploads)): ?>
        <p>Hiện chưa có file upload nào.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Ngày upload</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploads as $upload): ?>
                    <tr>
                        <td><a href="<?php echo htmlspecialchars($upload['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($upload['file_name']); ?></a></td>
                        <td><?php echo htmlspecialchars($upload['uploaded_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p><a href="logout.php">Đăng xuất</a></p>
</body>
</html>