<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <form method="post">
            <h2>Đăng ký tài khoản</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <input type="text" name="username" placeholder="Username" required autocomplete="username">
            <input type="email" name="email" placeholder="Email (Gmail)" required autocomplete="email">
            <input type="password" name="password" placeholder="Password" required autocomplete="new-password">
            <div class="password-hint">
                Password phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt
            </div>
            <input type="password" name="confirm_password" placeholder="Xác nhận Password" required autocomplete="new-password">
            <button type="submit">Đăng ký</button>
        </form>
        
        <div class="divider">
            <span>hoặc</span>
        </div>
        
        <a href="/google-login" class="google-btn">
            <img src="https://www.google.com/favicon.ico" alt="Google">
            Đăng ký với Google
        </a>
        
        <div class="login-link">
            Đã có tài khoản? <a href="/login">Đăng nhập ngay</a>
        </div>
    </div>
</body>
</html>
