<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File JAR - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Upload File JAR</h1>
        <div class="user-info">
            <a href="/dashboard" class="btn">Quay lại Dashboard</a>
            <a href="/logout" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="upload-section">
            <h2>Upload File JAR</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="upload-info">
                <p><strong>Quy định:</strong></p>
                <ul>
                    <li>Chỉ cho phép upload file .jar</li>
                    <li>Kích thước tối đa: 10MB</li>
                    <li>Tối đa 3 file cho mỗi user</li>
                </ul>
                <p>Số file đã upload: <strong><?= count($files) ?>/3</strong></p>
            </div>
            
            <?php if (count($files) < 3): ?>
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="jar_file">Chọn file JAR:</label>
                        <input type="file" name="jar_file" id="jar_file" accept=".jar" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            <?php else: ?>
                <div class="info">Bạn đã đạt giới hạn 3 file. Vui lòng xóa một file trước khi upload mới.</div>
            <?php endif; ?>
        </div>
        
        <div class="files-section">
            <h2>Danh sách file đã upload</h2>
            <?php if (empty($files)): ?>
                <div class="info">Chưa có file nào được upload.</div>
            <?php else: ?>
                <div class="files-list">
                    <?php foreach ($files as $file): ?>
                        <div class="file-item">
                            <div class="file-info">
                                <h3><?= htmlspecialchars($file['original_name']) ?></h3>
                                <p>Kích thước: <?= $this->formatFileSize($file['file_size']) ?></p>
                                <p>Upload lúc: <?= htmlspecialchars($file['uploaded_at']) ?></p>
                            </div>
                            <div class="file-actions">
                                <form method="post" action="/delete-file" onsubmit="return confirm('Bạn có chắc muốn xóa file này?');">
                                    <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .upload-section, .files-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .upload-section h2, .files-section h2 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .upload-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .upload-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .upload-info li {
            margin: 5px 0;
        }
        
        .upload-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 5px;
            background: #fafafa;
        }
        
        .files-list {
            display: grid;
            gap: 15px;
        }
        
        .file-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #28a745;
        }
        
        .file-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .file-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .file-actions form {
            margin: 0;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .upload-section, .files-section {
                padding: 20px;
            }
            
            .file-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .file-actions {
                width: 100%;
            }
            
            .file-actions form {
                width: 100%;
            }
            
            .file-actions button {
                width: 100%;
            }
        }
    </style>
    
    <?php
    // Helper function to format file size
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    ?>
</body>
</html>
