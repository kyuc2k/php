<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - <?= htmlspecialchars($user['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 1.8rem; color: #333; margin-bottom: 8px; }
        .header p { color: #666; font-size: 0.95rem; }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102,126,234,0.3); }
        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e0e0e0;
            margin-top: 15px;
        }
        .btn-secondary:hover { border-color: #667eea; color: #667eea; }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .message.error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .message.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .info-box {
            background: #e8f4fd;
            border-left: 4px solid #667eea;
            padding: 12px 16px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #555;
        }
        @media (max-width: 480px) {
            .container { padding: 30px 20px; }
            .header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $hasPassword ? 'Đổi mật khẩu' : 'Tạo mật khẩu mới' ?></h1>
            <p><?= htmlspecialchars($user['name']) ?></p>
        </div>
        
        <?php if (!$hasPassword): ?>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> Tài khoản của bạn được tạo bằng Google. Hãy tạo mật khẩu để có thể đăng nhập bằng email và mật khẩu.
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <?php if ($hasPassword): ?>
                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= $hasPassword ? 'Đổi mật khẩu' : 'Tạo mật khẩu' ?></button>
        </form>
        
        <a href="dashboard.php" class="btn btn-secondary" style="display:block;text-align:center;text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>
    </div>
</body>
</html>
