<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận Email - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Xác nhận Email</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
            <a href="/resend-verification" class="btn">Gửi lại mã xác nhận</a>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
            <a href="/login" class="btn">Đăng nhập ngay</a>
        <?php endif; ?>
    </div>
</body>
</html>
