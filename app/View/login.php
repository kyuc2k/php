<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <form method="post">
            <h2>Đăng nhập VPS Game</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <input type="email" name="email" placeholder="Email" required autocomplete="email">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit">Đăng nhập</button>
        </form>
        
        <div class="divider">
            <span>hoặc</span>
        </div>
        
        <a href="/google-login" class="google-btn">
            <img src="https://www.google.com/favicon.ico" alt="Google">
            Đăng nhập với Google
        </a>
        
        <div class="register-link">
            Chưa có tài khoản? <a href="/register">Đăng ký ngay</a>
        </div>
    </div>
</body>
</html>
