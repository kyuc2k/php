<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực Email - PDF Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verification-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .verification-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }
        .verification-icon::before {
            content: "✉";
            font-size: 36px;
            color: white;
        }
        h1 { color: #333; font-size: 24px; margin-bottom: 10px; font-weight: 600; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }
        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .message.error { background-color: #fee; color: #c33; border: 1px solid #fcc; }
        .message.success { background-color: #efe; color: #3c3; border: 1px solid #cfc; }
        .message.info { background-color: #e3f2fd; color: #1976d2; border: 1px solid #bbdefb; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; color: #555; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        input[type="text"]::placeholder { color: #999; }
        .button-group { display: flex; gap: 12px; margin-top: 24px; }
        button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
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
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-secondary:hover { background: #667eea; color: white; transform: translateY(-2px); }
        button:active { transform: translateY(0); }
        .resend-info { margin-top: 20px; font-size: 12px; color: #666; }
        @media (max-width: 480px) {
            .verification-container { padding: 30px 20px; }
            h1 { font-size: 20px; }
            .button-group { flex-direction: column; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon"></div>
        <h1>Xác thực Email</h1>
        <p class="subtitle">Mã xác thực đã được gửi đến email của bạn. Vui lòng nhập mã bên dưới.</p>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'expired') !== false || strpos($message, 'Invalid') !== false ? 'error' : (strpos($message, 'sent') !== false ? 'success' : 'info') ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="verificationForm">
            <div class="form-group">
                <label for="code">Mã xác thực</label>
                <input type="text" id="code" name="code" placeholder="Nhập mã 6 số" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <div class="button-group">
                <button type="submit" name="verify" class="btn-primary">Xác thực</button>
                <button type="submit" name="resend" class="btn-secondary">Gửi lại mã</button>
            </div>
        </form>
        <p class="resend-info">Không nhận được mã? Kiểm tra thư mục spam hoặc nhấn "Gửi lại mã"</p>
    </div>
    <script>
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
        window.addEventListener('load', function() {
            document.getElementById('code').focus();
        });
    </script>
</body>
</html>
