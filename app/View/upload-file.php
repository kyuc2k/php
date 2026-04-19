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
                                <p>Kích thước: <?= formatFileSize($file['file_size']) ?></p>
                                <p>Upload lúc: <?= htmlspecialchars($file['uploaded_at']) ?></p>
                            </div>
                            <div class="file-actions">
                                <button type="button" class="btn btn-danger" onclick="showDeleteModal(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name']) ?>')">Xóa</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Xác nhận xóa file</h3>
                <button class="modal-close" onclick="hideDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc muốn xóa file <strong id="deleteFileName"></strong> không?</p>
                <p>Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="hideDeleteModal()">Hủy</button>
                <button class="btn btn-danger" onclick="confirmDelete()">Xóa</button>
            </div>
        </div>
    </div>
    
    <script>
        let deleteFileId = null;
        
        function showDeleteModal(fileId, fileName) {
            deleteFileId = fileId;
            document.getElementById('deleteFileName').textContent = fileName;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteFileId = null;
        }
        
        function confirmDelete() {
            if (deleteFileId) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/delete-file';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'file_id';
                input.value = deleteFileId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                hideDeleteModal();
            }
        }
    </script>
    
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
            cursor: pointer;
        }
        
        .form-group input[type="file"]::file-selector-button {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .form-group input[type="file"]::file-selector-button:hover {
            background: #c0392b;
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
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #e74c3c;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-body p {
            margin: 10px 0;
            color: #333;
        }
        
        .modal-body strong {
            color: #e74c3c;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-footer .btn {
            padding: 10px 20px;
            min-width: 100px;
        }
        
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer .btn {
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
