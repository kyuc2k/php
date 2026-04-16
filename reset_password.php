<?php

session_start();
require 'config.php';
require_once 'activity_logger.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';
$messageType = '';
$resetSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_code'])) {
        $code = trim($_POST['code']);

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND verification_code_expires > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['reset_verified'] = true;
            $message = "Mã xác nhận đúng! Hãy nhập mật khẩu mới.";
            $messageType = "success";
        } else {
            $message = "Mã xác nhận không đúng hoặc đã hết hạn.";
            $messageType = "error";
        }
        $stmt->close();
    } elseif (isset($_POST['reset_password']) && isset($_SESSION['reset_verified'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (strlen($newPassword) < 6) {
            $message = "Mật khẩu phải có ít nhất 6 ký tự.";
            $messageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Mật khẩu xác nhận không khớp.";
            $messageType = "error";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, verification_code = NULL, verification_code_expires = NULL WHERE email = ?");
            $stmt->bind_param("ss", $newHash, $email);
            $stmt->execute();
            $stmt->close();

            // Log activity
            $stmt_uid = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_uid->bind_param("s", $email);
            $stmt_uid->execute();
            $uidRow = $stmt_uid->get_result()->fetch_assoc();
            $stmt_uid->close();
            if ($uidRow) log_activity($conn, $uidRow['id'], 'reset_password', 'Đặt lại mật khẩu thành công');

            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);

            $_SESSION['message'] = "Đặt lại mật khẩu thành công! Hãy đăng nhập với mật khẩu mới.";
            header("Location: login.php");
            exit();
        }
    } elseif (isset($_POST['resend'])) {
        // Resend code
        $code = rand(100000, 999999);
        $stmt_update = $conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
        $stmt_update->bind_param("ss", $code, $email);
        $stmt_update->execute();
        $stmt_update->close();

        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';
        require 'PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('GMAIL_USERNAME');
        $mail->Password = getenv('GMAIL_APP_PASSWORD');
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(getenv('GMAIL_USERNAME'), 'PDF Manager');
        $mail->addAddress($email);

        $mail->isHTML(false);
        $mail->Subject = "Reset Password - PDF Manager";
        $mail->Body = "Mã đặt lại mật khẩu mới của bạn là: $code\n\nMã này có hiệu lực trong 10 phút.";

        if ($mail->send()) {
            $message = "Đã gửi lại mã xác nhận. Vui lòng kiểm tra email.";
            $messageType = "success";
        } else {
            $message = "Không thể gửi email. Vui lòng thử lại.";
            $messageType = "error";
        }
    }
}

$isVerified = isset($_SESSION['reset_verified']);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - PDF Manager</title>
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
            max-width: 450px;
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
            line-height: 1.5;
        }

        .email-badge {
            background: #e8f0fe;
            color: #1a56db;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .form-group input::placeholder {
            color: #999;
        }

        .code-input {
            text-align: center;
            font-size: 1.5rem !important;
            letter-spacing: 8px;
            font-weight: 700;
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

        .btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            margin-top: 10px;
            text-transform: none;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
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

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .step.done {
            background: #28a745;
            color: white;
        }

        .step.inactive {
            background: #e0e0e0;
            color: #999;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #e0e0e0;
        }

        .step-line.done {
            background: #28a745;
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
                padding: 10px 14px;
                font-size: 0.95rem;
            }

            .code-input {
                font-size: 1.3rem !important;
                letter-spacing: 5px;
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
                <i class="fas fa-<?= $isVerified ? 'key' : 'envelope-open-text' ?>"></i>
            </div>
            <h1><?= $isVerified ? 'Đặt mật khẩu mới' : 'Nhập mã xác nhận' ?></h1>
            <p><?= $isVerified ? 'Tạo mật khẩu mới cho tài khoản của bạn' : 'Chúng tôi đã gửi mã 6 chữ số đến email của bạn' ?></p>
        </div>

        <div class="step-indicator">
            <div class="step done"><i class="fas fa-check"></i></div>
            <div class="step-line <?= $isVerified ? 'done' : '' ?>"></div>
            <div class="step <?= $isVerified ? 'done' : 'active' ?>"><?= $isVerified ? '<i class="fas fa-check"></i>' : '2' ?></div>
            <div class="step-line"></div>
            <div class="step <?= $isVerified ? 'active' : 'inactive' ?>">3</div>
        </div>

        <div class="email-badge">
            <i class="fas fa-envelope"></i>
            <?= htmlspecialchars($email) ?>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$isVerified): ?>
            <!-- Step 2: Enter verification code -->
            <form method="post">
                <div class="form-group">
                    <label for="code">Mã xác nhận</label>
                    <input type="text" id="code" name="code" class="code-input" placeholder="000000" maxlength="6" required autofocus>
                </div>

                <button type="submit" name="verify_code" class="btn btn-primary">
                    <i class="fas fa-check"></i> Xác nhận
                </button>
            </form>

            <form method="post" style="margin-top: 5px;">
                <button type="submit" name="resend" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Gửi lại mã
                </button>
            </form>
        <?php else: ?>
            <!-- Step 3: Set new password -->
            <form method="post">
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
                    <label for="confirm_password">Xác nhận mật khẩu</label>
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

                <button type="submit" name="reset_password" class="btn btn-primary">
                    <i class="fas fa-save"></i> Đặt lại mật khẩu
                </button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay về đăng nhập
        </a>
    </div>

    <script>
        // Auto-format code input
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }

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
        if (newPassword && confirmPassword) {
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
        }
    </script>
</body>
</html>
