<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - PDF Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/common.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            min-height: 550px;
        }
        .auth-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
        }
        .auth-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .auth-left p { font-size: 1.1rem; opacity: 0.9; line-height: 1.6; margin-bottom: 30px; }
        .auth-left .features { text-align: left; max-width: 300px; }
        .auth-left .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        .auth-left .features i { margin-right: 10px; font-size: 1.2rem; }
        .auth-right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-title { font-size: 1.8rem; font-weight: 700; color: #333; margin-bottom: 8px; }
        .auth-subtitle { color: #666; font-size: 0.95rem; margin-bottom: 30px; }
        .auth-subtitle a { color: #667eea; text-decoration: none; font-weight: 600; }
        .auth-subtitle a:hover { text-decoration: underline; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group input::placeholder { color: #999; }
        .btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-google {
            background: white;
            color: #333;
            border: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            text-decoration: none;
        }
        .btn-google:hover { background: #f8f9fa; border-color: #667eea; }
        .btn-google i { font-size: 1.2rem; color: #4285f4; }
        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .message-error { background: #fee; color: #c00; border: 1px solid #fcc; }
        .message-success { background: #efe; color: #060; border: 1px solid #cfc; }
        .message-warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e0e0e0;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #666;
            font-size: 0.9rem;
        }
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 0.9rem;
        }
        .auth-footer a { color: #667eea; text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .auth-container { flex-direction: column; max-width: 400px; }
            .auth-left { padding: 30px 20px; min-height: 200px; }
            .auth-left h1 { font-size: 2rem; }
            .auth-left .features { display: none; }
            .auth-right { padding: 30px 20px; }
        }
        @media (max-width: 480px) {
            body { padding: 10px; }
            .auth-container { max-width: 100%; }
            .auth-left { padding: 20px 15px; }
            .auth-right { padding: 20px 15px; }
            .form-group input { padding: 10px 14px; font-size: 0.95rem; }
            .btn { padding: 12px 16px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1>Chào mừng!</h1>
            <p>Hệ thống quản lý file PDF thông minh và an toàn</p>
            <ul class="features">
                <li><i class="fas fa-shield-alt"></i> Bảo mật cao cấp</li>
                <li><i class="fas fa-cloud-upload-alt"></i> Upload file dễ dàng</li>
                <li><i class="fas fa-user-shield"></i> Xác thực hai lớp</li>
                <li><i class="fas fa-mobile-alt"></i> Responsive mọi thiết bị</li>
            </ul>
        </div>
        <div class="auth-right">
            <h2 class="auth-title">Đăng nhập</h2>
            <p class="auth-subtitle">Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>

            <?php if ($message): ?>
                <div class="message <?php
                    if (strpos($message, 'thiết bị khác') !== false || strpos($message, 'đăng xuất') !== false) {
                        echo 'message-warning';
                    } elseif (strpos($message, 'failed') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'not found') !== false) {
                        echo 'message-error';
                    } else {
                        echo 'message-success';
                    }
                ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 0.9rem; font-weight: 500;">Quên mật khẩu?</a>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Đăng nhập</button>
                <div class="divider"><span>Hoặc</span></div>
                <a href="<?= $google_login_url ?>" class="btn btn-google">
                    <i class="fab fa-google"></i>
                    Đăng nhập với Google
                </a>
            </form>
            <div class="auth-footer">Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a></div>
        </div>
    </div>
</body>
</html>
