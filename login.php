<?php

session_start();
require 'config.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);

$google_login_url = "https://accounts.google.com/o/oauth2/auth?"
    . "client_id=".$client_id
    . "&redirect_uri=".$redirect_uri
    . "&response_type=code"
    . "&scope=email profile"
    . "&access_type=online";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, email_verified FROM users WHERE email = ? AND password IS NOT NULL");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            if ($row['email_verified'] == 1) {
                // Generate unique session token
                $sessionToken = bin2hex(random_bytes(32));
                $stmt_token = $conn->prepare("UPDATE users SET session_token = ? WHERE id = ?");
                $stmt_token->bind_param("si", $sessionToken, $row['id']);
                $stmt_token->execute();
                $stmt_token->close();

                $_SESSION['user'] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $email,
                ];
                $_SESSION['session_token'] = $sessionToken;
                header("Location: dashboard.php");
                exit();
            } else {
                // Email not verified - redirect to verification page
                $_SESSION['email'] = $email;
                $message = "Please verify your email before logging in. A verification code has been sent to your email.";
                
                // Generate and send new verification code
                $code = rand(100000, 999999);
                $stmt_update = $conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email = ?");
                $stmt_update->bind_param("ss", $code, $email);
                $stmt_update->execute();
                $stmt_update->close();
                
                // Send email using PHPMailer
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

                $mail->setFrom(getenv('GMAIL_USERNAME'), 'Your App');
                $mail->addAddress($email);

                $mail->isHTML(false);
                $mail->Subject = "Email Verification Code";
                $mail->Body = "Your verification code is: $code";

                if ($mail->send()) {
                    header("Location: verify.php");
                    exit();
                }
            }
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "User not found.";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - PDF Manager</title>
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
            background-clip: text;
        }

        .auth-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .auth-left .features {
            text-align: left;
            max-width: 300px;
        }

        .auth-left .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .auth-left .features i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .auth-right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .auth-subtitle a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-subtitle a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        .btn-google:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }

        .btn-google i {
            font-size: 1.2rem;
            color: #4285f4;
        }

        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
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

        .message-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

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

        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-width: 400px;
            }

            .auth-left {
                padding: 30px 20px;
                min-height: 200px;
            }

            .auth-left h1 {
                font-size: 2rem;
            }

            .auth-left .features {
                display: none;
            }

            .auth-right {
                padding: 30px 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .auth-container {
                max-width: 100%;
            }

            .auth-left {
                padding: 20px 15px;
            }

            .auth-right {
                padding: 20px 15px;
            }

            .form-group input {
                padding: 10px 14px;
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
                    } elseif (strpos($message, 'failed') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'not found') !== false || strpos($message, 'do not match') !== false || strpos($message, 'already') !== false) {
                        echo 'message-error';
                    } else {
                        echo 'message-success';
                    }
                ?>">
                    <?php echo htmlspecialchars($message); ?>
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
                
                <button type="submit" name="login" class="btn btn-primary">Đăng nhập</button>
                
                <div class="divider">
                    <span>Hoặc</span>
                </div>
                
                <a href="<?= $google_login_url ?>" class="btn btn-google">
                    <i class="fab fa-google"></i>
                    Đăng nhập với Google
                </a>
            </form>

            <div class="auth-footer">
                Chưa có tài khoản? <a href="register.php">Đăng ký tại đây</a>
            </div>
        </div>
    </div>

    <script>
        // Add input animations
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>