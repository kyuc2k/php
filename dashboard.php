<?php

session_start();
require 'config.php';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['file_id'])) {
    $fileId = (int)$_POST['file_id'];
    $stmt = $conn->prepare("SELECT file_name, file_path, user_id FROM uploads WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $fileInfo = $result->fetch_assoc();
    $stmt->close();

    if ($fileInfo && $fileInfo['user_id'] == $userId) {
        $fullPath = __DIR__ . '/' . $fileInfo['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $stmt = $conn->prepare("DELETE FROM uploads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $fileId, $userId);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php");
    exit();
}

// Get user statistics
$stmt_files = $conn->prepare("SELECT COUNT(*) as total_files FROM uploads WHERE user_id = ?");
$stmt_files->bind_param("i", $user['id']);
$stmt_files->execute();
$result_files = $stmt_files->get_result();
$stats = $result_files->fetch_assoc();
$stats['total_size'] = 0; // Default to 0 since we don't have file_size column
$stmt_files->close();

// Get recent files
$stmt_recent = $conn->prepare("SELECT id, file_name, file_path, uploaded_at FROM uploads WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 5");
$stmt_recent->bind_param("i", $user['id']);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
$recent_files = [];
while ($row = $result_recent->fetch_assoc()) {
    $recent_files[] = $row;
}
$stmt_recent->close();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($user['name']) ?></title>
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
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .user-avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            border: 4px solid #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .user-details h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .user-details p {
            color: #666;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-details i {
            color: #667eea;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .recent-files {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .file-list {
            list-style: none;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .file-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            word-break: break-all;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        .file-date {
            color: #666;
            font-size: 0.9rem;
        }

        .file-actions {
            display: flex;
            gap: 10px;
        }

        .file-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            background: white;
            color: #666;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .file-action:hover {
            background: #667eea;
            color: white;
        }

        .file-action.delete:hover {
            background: #dc3545;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .quick-actions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .action-item {
            padding: 20px;
            border-radius: 10px;
            background: #f8f9fa;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }

        .action-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
        }

        .action-item i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .action-item span {
            font-weight: 600;
            font-size: 0.9rem;
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

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .user-info {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }

            .user-avatar,
            .user-avatar-placeholder {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .user-details h1 {
                font-size: 1.5rem;
            }

            .stat-value {
                font-size: 2rem;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="user-info">
                <?php if (isset($user['picture'])): ?>
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="<?= htmlspecialchars($user['name']) ?>" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <div class="user-details">
                    <h1><?= htmlspecialchars($user['name']) ?></h1>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="upload.php" class="btn btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload PDF
                </a>
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                    Đăng xuất
                </a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="stat-value"><?= number_format($stats['total_files']) ?></div>
                <div class="stat-label">Tổng số file</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-value"><?= $stats['total_size'] ? number_format($stats['total_size'] / 1024 / 1024, 2) : '0.00' ?> MB</div>
                <div class="stat-label">Dung lượng đã dùng</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?= date('d/m/Y') ?></div>
                <div class="stat-label">Đăng nhập hôm nay</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="recent-files">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-clock"></i>
                        File gần đây
                    </h2>
                    <a href="upload.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">
                        <i class="fas fa-plus"></i>
                        Thêm mới
                    </a>
                </div>
                
                <?php if (empty($recent_files)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Chưa có file nào được tải lên</p>
                        <p>Hãy bắt đầu bằng việc tải lên file PDF đầu tiên!</p>
                    </div>
                <?php else: ?>
                    <ul class="file-list">
                        <?php foreach ($recent_files as $file): ?>
                            <li class="file-item">
                                <div class="file-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="file-info">
                                    <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                                    <div class="file-date"><?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?></div>
                                </div>
                                <div class="file-actions">
                                    <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="file-action" title="Xem file">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="file-action delete" title="Xóa file" onclick="showDeleteModal(<?= $file['id'] ?>, '<?= htmlspecialchars($file['file_name'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Thao tác nhanh
                    </h2>
                </div>
                
                <div class="action-grid">
                    <a href="upload.php" class="action-item">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload File</span>
                    </a>
                    
                    <a href="upload.php" class="action-item">
                        <i class="fas fa-folder-open"></i>
                        <span>Quản lý File</span>
                    </a>
                    
                    <a href="#" class="action-item" onclick="alert('Tính năng đang phát triển!')">
                        <i class="fas fa-chart-bar"></i>
                        <span>Thống kê</span>
                    </a>
                    
                    <a href="#" class="action-item" onclick="alert('Tính năng đang phát triển!')">
                        <i class="fas fa-cog"></i>
                        <span>Cài đặt</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="modal-title">Xác nhận xóa file</h3>
            <div class="modal-message">
                Bạn có chắc chắn muốn xóa file này không?
                <div class="modal-file-name" id="modalFileName"></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Xóa
                </button>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="file_id" id="deleteFileId">
    </form>

    <script>
        function showDeleteModal(fileId, fileName) {
            document.getElementById('modalFileName').textContent = fileName;
            document.getElementById('deleteFileId').value = fileId;
            document.getElementById('deleteModal').classList.add('show');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            document.getElementById('deleteForm').submit();
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>