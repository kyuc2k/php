<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - PDF Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .auth-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .auth-icon i { font-size: 36px; color: white; }
        h1 { font-size: 1.8rem; color: #333; margin-bottom: 10px; font-weight: 700; }
        .subtitle { color: #666; font-size: 0.95rem; margin-bottom: 30px; }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .message.error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .message.success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; font-size: 0.9rem; }
        input {
            width: 100%;
            padding: 12px 16px;
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
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-top: 10px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .btn-secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-secondary:hover { background: #667eea; color: white; }
        .code-inputs { display: flex; gap: 8px; justify-content: center; margin-bottom: 20px; }
        .code-inputs input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-icon"><i class="fas fa-key"></i></div>
        <h1>Đặt lại mật khẩu</h1>
        <p class="subtitle">Email: <?= htmlspecialchars($email) ?></p>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'verify'): ?>
            <p style="color:#666;margin-bottom:20px;">Nhập mã xác nhận đã gửi đến email của bạn</p>
            <form method="post">
                <div class="form-group">
                    <label>Mã xác nhận</label>
                    <input type="text" name="code" placeholder="Mã 6 số" maxlength="6" pattern="[0-9]{6}" required>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" name="verify_code" class="btn" style="flex:2;">Xác nhận</button>
                    <button type="submit" name="resend" class="btn btn-secondary" style="flex:1;">Gửi lại</button>
                </div>
            </form>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>
                <button type="submit" name="reset_password" class="btn">Đặt lại mật khẩu</button>
            </form>
        <?php endif; ?>
        
        <div style="margin-top:25px;font-size:0.9rem;">
            <a href="login.php" style="color:#667eea;text-decoration:none;"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
        </div>
    </div>
</body>
</html>
