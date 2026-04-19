<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/layout.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <h1>VPS Treo Game</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li><a href="/dashboard">Dashboard</a></li>
                        <li><a href="/upload-file">Upload File</a></li>
                        <li><a href="/deposit">Nạp tiền</a></li>
                        <li><a href="/logs">Logs</a></li>
                        <li class="balance-display">Số dư: <?= number_format($balance ?? 0, 0, ',', '.') ?> VNĐ</li>
                        <li><a href="/logout" class="btn-logout">Đăng xuất</a></li>
                    <?php else: ?>
                        <li><a href="/login">Đăng nhập</a></li>
                        <li><a href="/register" class="btn-register">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    
    <main class="main-content">
