<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <div class="mobile-menu-btn" onclick="toggleSidebar()">
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <h3>Menu</h3>
                <button class="close-sidebar" onclick="toggleSidebar()">×</button>
            </div>
            <div class="sidebar-user">
                <span>Xin chào, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></span>
            </div>
            <div class="sidebar-menu">
                <a href="/dashboard" onclick="toggleSidebar()">Dashboard</a>
                <a href="/logs" onclick="toggleSidebar()">Xem nhật ký hoạt động</a>
                <a href="/change-password" onclick="toggleSidebar()">Đổi mật khẩu</a>
                <a href="/upload-file" onclick="toggleSidebar()">Upload File JAR</a>
                <a href="/logout" onclick="toggleSidebar()" class="logout-link">Đăng xuất</a>
            </div>
        </div>
    </div>
    
    <div class="dashboard-header">
        <h1>Dashboard VPS Game</h1>
        <div class="user-info">
            <a href="/logs" class="btn">Xem nhật ký hoạt động</a>
            <a href="/change-password" class="btn">Đổi mật khẩu</a>
            <a href="/upload-file" class="btn">Upload File JAR</a>
            <span>Xin chào, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></span>
            <a href="/logout" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
    
    <?php if (isset($_GET['success']) && $_GET['success'] == 'password_changed'): ?>
        <div class="success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #28a745; border-radius: 5px; border: 1px solid #c3e6cb;">
            Đổi mật khẩu thành công!
        </div>
    <?php endif; ?>
    
    <a href="/vm/create" class="btn-create">Tạo VPS Game mới</a>
    
    <div class="files-section">
        <h2>Danh sách file JAR đã upload (<?= count($files) ?>/3)</h2>
        <?php if (empty($files)): ?>
            <div class="info">Chưa có file nào được upload. <a href="/upload-file" class="btn btn-primary">Upload file đầu tiên</a></div>
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
                            <a href="/upload-file" class="btn">Quản lý file</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="container-list">
        <?php if (empty($instances)): ?>
            <div class="card text-center">
                <p>Chưa có VPS game nào. <a href="/vm/create" class="btn btn-primary">Tạo VPS game đầu tiên của bạn</a></p>
            </div>
        <?php else: ?>
            <?php foreach ($instances as $row): ?>
                <div class="container-item">
                    <h3>VPS Game: <?= htmlspecialchars($row['container_name']) ?></h3>
                    <p>Trạng thái: <span class="status <?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></span></p>
                    <div class="actions">
                        <a href="/vm/start?id=<?= $row['id'] ?>" class="btn-start">Bắt đầu</a>
                        <a href="/vm/stop?id=<?= $row['id'] ?>" class="btn-stop">Dừng</a>
                        <a href="http://103.245.236.153:<?= $row['port'] ?>/vnc.html" target="_blank" class="btn-open">Mở VPS</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div id="session-alert" style="display: none; position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 20px; border-radius: 5px; z-index: 10000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <p id="session-message"></p>
        <button onclick="window.location.href='/login'" style="background: white; color: #dc3545; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer; margin-top: 10px; font-weight: bold;">Đăng nhập lại</button>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        function checkSession() {
            fetch('/validate-session')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        document.getElementById('session-alert').style.display = 'block';
                        document.getElementById('session-message').textContent = data.message || 'Phiên đăng nhập đã hết hạn';
                    }
                })
                .catch(error => console.error('Session check error:', error));
        }
        
        // Check session every 30 seconds
        setInterval(checkSession, 30000);
        
        // Check session on page load
        checkSession();
    </script>
    
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
