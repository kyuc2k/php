<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi lại mã xác nhận - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <form method="post">
            <h2>Gửi lại mã xác nhận</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php if (strpos($success, 'đã được xác nhận') !== false): ?>
                    <a href="/login" class="btn">Đăng nhập ngay</a>
                <?php endif; ?>
            <?php endif; ?>
            <input type="email" name="email" placeholder="Email" required autocomplete="email">
            <button type="submit">Gửi lại mã</button>
        </form>
        <div class="register-link">
            <a href="/login">Quay lại đăng nhập</a>
        </div>
    </div>
</body>
</html>
