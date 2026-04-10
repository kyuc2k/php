<?php
session_start();

// Kiểm tra nếu chưa login thì chuyển hướng sang login.php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

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
    $message = 'Thư mục uploads không có quyền ghi. Vui lòng kiểm tra quyền của thư mục.';
    $uploadAvailable = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];

    // Kiểm tra lỗi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Lỗi upload file.';
    } elseif ($file['type'] !== 'application/pdf') {
        $message = 'Chỉ chấp nhận file PDF.';
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB
        $message = 'File quá lớn (tối đa 10MB).';
    } else {
        // Tạo tên file duy nhất
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Có thể lưu vào database nếu cần
            // $stmt = $conn->prepare("INSERT INTO uploads (user_id, file_name, file_path) VALUES (?, ?, ?)");
            // $stmt->bind_param("iss", $_SESSION['user']['id'], $fileName, $filePath);
            // $stmt->execute();

            $message = 'Upload thành công: <a href="uploads/' . $fileName . '" target="_blank">' . $fileName . '</a>';
        } else {
            $message = 'Lỗi lưu file.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Upload PDF</title>
</head>
<body>
    <h1>Upload File PDF</h1>

    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="pdf_file" accept=".pdf" required>
        <button type="submit">Upload</button>
    </form>

    <p><a href="logout.php">Đăng xuất</a></p>
</body>
</html>