<?php require_once __DIR__ . '/layout/header.php'; ?>

    <div class="page-content">
        <?php if (isset($_GET['success']) && $_GET['success'] == 'password_changed'): ?>
            <div class="success" style="margin-bottom: 20px; padding: 15px; background: #d4edda; color: #28a745; border-radius: 5px; border: 1px solid #c3e6cb;">
                Đổi mật khẩu thành công!
            </div>
        <?php endif; ?>
        
        <h1>Dashboard VPS Game</h1>
        
        <div class="rental-section">
            <h2>Gói thuê VPS (<?= count($rentals) ?>)</h2>
            <?php if (empty($rentals)): ?>
                <div class="info">Bạn chưa thuê gói nào. <a href="/rental" class="btn btn-primary">Thuê gói ngay</a></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên gói</th>
                                <th>Thời hạn</th>
                                <th>Giá</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rental['package_name']) ?></td>
                                    <td><?= $rental['duration_months'] ?> tháng</td>
                                    <td><?= number_format($rental['price'], 0, ',', '.') ?> VNĐ</td>
                                    <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                    <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                    <td>
                                        <?php 
                                        $endDate = strtotime($rental['end_date']);
                                        $now = time();
                                        if ($rental['status'] == 'active' && $endDate > $now): ?>
                                            <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Đang hoạt động</span>
                                        <?php else: ?>
                                            <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Đã hết hạn</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="files-section">
            <h2>Danh sách file JAR đã upload (<?= count($files) ?>/3)</h2>
            <?php if (empty($files)): ?>
                <div class="info">Chưa có file nào được upload. <a href="/upload-file" class="btn btn-primary">Upload file đầu tiên</a></div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên file</th>
                                <th>Kích thước</th>
                                <th>Upload lúc</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?= htmlspecialchars($file['original_name']) ?></td>
                                    <td><?= formatFileSize($file['file_size']) ?></td>
                                    <td><?= htmlspecialchars($file['uploaded_at']) ?></td>
                                    <td>
                                        <a href="/upload-file" class="btn">Quản lý</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="container-list">
            <?php if (empty($instances)): ?>
                <div class="card text-center">
                    <p>Chưa có VPS game nào. <a href="/rental" class="btn btn-primary">Thuê VPS game đầu tiên của bạn</a></p>
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
    </div>

    <div id="session-alert" style="display: none; position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 20px; border-radius: 5px; z-index: 10000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <p id="session-message"></p>
        <button onclick="window.location.href='/login'" style="background: white; color: #dc3545; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer; margin-top: 10px; font-weight: bold;">Đăng nhập lại</button>
    </div>

    <script>
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
        
        setInterval(checkSession, 30000);
        checkSession();
    </script>

    <?php
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

<?php require_once __DIR__ . '/layout/footer.php'; ?>
