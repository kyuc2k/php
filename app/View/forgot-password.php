<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    <div class="login-container">
        <form method="post" id="forgotPasswordForm">
            <h2>Quên mật khẩu</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <input type="email" name="email" placeholder="Email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <button type="submit">Gửi link đặt lại mật khẩu</button>
        </form>
        <div class="register-link">
            <a href="/login">Quay lại đăng nhập</a>
        </div>
    </div>
    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        document.getElementById('forgotPasswordForm').addEventListener('submit', function() {
            showLoading();
        });
    </script>
</body>
</html>
