<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    <div class="login-container">
        <form method="post" id="resetPasswordForm">
            <h2>Đặt lại mật khẩu</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            <input type="password" name="new_password" placeholder="Mật khẩu mới" required autocomplete="new-password">
            <div class="password-hint">
                Password phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt
            </div>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required autocomplete="new-password">
            <button type="submit">Đặt lại mật khẩu</button>
        </form>
        <div class="register-link">
            <a href="/login">Quay lại đăng nhập</a>
        </div>
    </div>
    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        document.getElementById('resetPasswordForm').addEventListener('submit', function() {
            showLoading();
        });
    </script>
</body>
</html>
