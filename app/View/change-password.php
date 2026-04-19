<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    <div class="login-container">
        <form method="post" id="changePasswordForm">
            <h2>Đổi mật khẩu</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['user']['google_id'])): ?>
                <div class="info">Bạn đang đăng nhập bằng Google. Bạn có thể đặt mật khẩu để đăng nhập bằng email/password sau này.</div>
            <?php else: ?>
                <input type="password" name="current_password" placeholder="Mật khẩu hiện tại" required autocomplete="current-password">
            <?php endif; ?>
            <input type="password" name="new_password" placeholder="Mật khẩu mới" required autocomplete="new-password" value="<?= htmlspecialchars($_POST['new_password'] ?? '') ?>">
            <div class="password-hint">
                Password phải có ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường, số và ký tự đặc biệt
            </div>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required autocomplete="new-password" value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>">
            <button type="submit"><?= !empty($_SESSION['user']['google_id']) ? 'Đặt mật khẩu' : 'Đổi mật khẩu' ?></button>
        </form>
        <div class="register-link">
            <a href="/dashboard">Quay lại Dashboard</a>
        </div>
    </div>
    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        document.getElementById('changePasswordForm').addEventListener('submit', function() {
            showLoading();
        });
    </script>
</body>
</html>
