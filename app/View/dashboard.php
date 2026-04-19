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
    <div class="dashboard-header">
        <h1>Dashboard VPS Game</h1>
        <div class="user-info">
            <a href="/logs" class="btn">Xem nhật ký hoạt động</a>
            <span>Xin chào, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></span>
            <a href="/logout" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
    
    <a href="/vm/create" class="btn-create">Tạo VPS Game mới</a>
    
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
</body>
</html>
