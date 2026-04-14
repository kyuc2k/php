<?php

session_start();
require 'config.php';
require 'auth_check.php';

$user = $_SESSION['user'];
$message = '';
$messageType = '';

// Check if user has a password (Google-only users don't)
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$hasPassword = !empty($row['password']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($hasPassword) {
        $currentPassword = $_POST['current_password'];

        // Verify current password
        if (!password_verify($currentPassword, $row['password'])) {
            $message = "Mật khẩu hiện tại không đúng.";
            $messageType = "error";
        } elseif (strlen($newPassword) < 6) {
            $message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Mật khẩu xác nhận không khớp.";
            $messageType = "error";
        } elseif (password_verify($newPassword, $row['password'])) {
            $message = "Mật khẩu mới không được trùng với mật khẩu hiện tại.";
            $messageType = "error";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $newHash, $user['id']);
            $stmt_update->execute();
            $stmt_update->close();
            $_SESSION['message'] = "Đổi mật khẩu thành công!";
            header("Location: dashboard.php");
            exit();
        }
    } else {
        // Google user setting password for the first time
        if (strlen($newPassword) < 6) {
            $message = "Mật khẩu phải có ít nhất 6 ký tự.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Mật khẩu xác nhận không khớp.";
            $messageType = "error";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_update->bind_param("si", $newHash, $user['id']);
            $stmt_update->execute();
            $stmt_update->close();
            $_SESSION['message'] = "Tạo mật khẩu thành công! Bạn có thể dùng email và mật khẩu để đăng nhập.";
            header("Location: dashboard.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - PDF Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-header .icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 16px;
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

        .form-group input::placeholder {
            color: #999;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #667eea;
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

        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }

        .message-success {
            background: #efe;
            color: #060;
            border: 1px solid #cfc;
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
        }

        .password-rules {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .password-rules h4 {
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 8px;
        }

        .password-rules ul {
            list-style: none;
            font-size: 0.85rem;
            color: #666;
        }

        .password-rules li {
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-rules li i {
            font-size: 0.75rem;
            color: #ccc;
        }

        .password-rules li.valid i {
            color: #28a745;
        }

        .google-notice {
            background: #e8f0fe;
            border: 1px solid #b8d4fe;
            color: #1a56db;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 25px 20px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .form-group input {
                padding: 10px 40px 10px 14px;
                font-size: 0.95rem;
            }

            .btn {
                padding: 12px 16px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="icon">
                <i class="fas fa-key"></i>
            </div>
            <h1><?= $hasPassword ? 'Đổi mật khẩu' : 'Tạo mật khẩu' ?></h1>
            <p><?= $hasPassword ? 'Cập nhật mật khẩu để bảo mật tài khoản' : 'Tạo mật khẩu để đăng nhập bằng email' ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$hasPassword): ?>
            <div class="google-notice">
                <i class="fab fa-google"></i>
                Bạn đang dùng tài khoản Google. Tạo mật khẩu để có thể đăng nhập bằng email.
            </div>
        <?php endif; ?>

        <form method="post">
            <?php if ($hasPassword): ?>
                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <div class="input-wrapper">
                        <input type="password" id="current_password" name="current_password" placeholder="Nhập mật khẩu hiện tại" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <div class="input-wrapper">
                    <input type="password" id="new_password" name="new_password" placeholder="Nhập mật khẩu mới" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <div class="input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu mới" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="password-rules">
                <h4>Yêu cầu mật khẩu:</h4>
                <ul>
                    <li id="rule-length"><i class="fas fa-circle"></i> Ít nhất 6 ký tự</li>
                    <li id="rule-match"><i class="fas fa-circle"></i> Mật khẩu xác nhận phải khớp</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                <?= $hasPassword ? 'Cập nhật mật khẩu' : 'Tạo mật khẩu' ?>
            </button>
        </form>

        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay về Dashboard
        </a>
    </div>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Real-time password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const ruleLength = document.getElementById('rule-length');
        const ruleMatch = document.getElementById('rule-match');

        function checkRules() {
            if (newPassword.value.length >= 6) {
                ruleLength.classList.add('valid');
                ruleLength.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
            } else {
                ruleLength.classList.remove('valid');
                ruleLength.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
            }

            if (confirmPassword.value && newPassword.value === confirmPassword.value) {
                ruleMatch.classList.add('valid');
                ruleMatch.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
            } else {
                ruleMatch.classList.remove('valid');
                ruleMatch.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
            }
        }

        newPassword.addEventListener('input', checkRules);
        confirmPassword.addEventListener('input', checkRules);
    </script>
</body>
</html>
