<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập mã xác nhận - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/login.css">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    <div class="login-container">
        <form method="post" id="verifyForm">
            <h2>Nhập mã xác nhận</h2>
            <?php if (isset($_GET['resent']) && $_GET['resent'] == 'true'): ?>
                <div class="success">Đã gửi lại mã xác nhận. Vui lòng kiểm tra email.</div>
            <?php else: ?>
                <div class="info">Mã xác nhận đã được gửi về email của bạn. Vui lòng kiểm tra email.</div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <input type="text" name="code" placeholder="Mã 6 số" required pattern="[0-9]{6}" maxlength="6" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">
            <button type="submit">Xác nhận</button>
        </form>
        <div class="register-link">
            <a href="/resend-verification" onclick="showLoading()">Gửi lại mã</a>
        </div>
        <div class="register-link">
            <a href="/login">Quay lại đăng nhập</a>
        </div>
    </div>
    <script>
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }
        document.getElementById('verifyForm').addEventListener('submit', function() {
            showLoading();
        });
    </script>
</body>
</html>
