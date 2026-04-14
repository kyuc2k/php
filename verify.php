<?php
session_start();
require 'config.php';

$message = '';

// Check if user has email session (for direct access prevention)
if (!isset($_SESSION['email']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $message = "Please complete the registration process first.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify'])) {
        $code = trim($_POST['code']);
        if (empty($code)) {
            $message = "Please enter the verification code.";
        } else {
            if (!isset($_SESSION['email'])) {
                $message = "Session expired. Please try logging in again.";
            } else {
                $email = $_SESSION['email'];

                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_code = ? AND verification_code_expires > NOW()");
                $stmt->bind_param("ss", $email, $code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $stmt->close();
                    $stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->close();

                    // Get user info and login
                    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $user_result = $stmt->get_result();
                    $user_row = $user_result->fetch_assoc();
                    $stmt->close();

                    // Generate unique session token
                    $sessionToken = bin2hex(random_bytes(32));
                    $stmt_token = $conn->prepare("UPDATE users SET session_token = ? WHERE id = ?");
                    $stmt_token->bind_param("si", $sessionToken, $user_row['id']);
                    $stmt_token->execute();
                    $stmt_token->close();

                    $_SESSION['user'] = [
                        'id' => $user_row['id'],
                        'name' => $user_row['name'],
                        'email' => $email,
                    ];
                    $_SESSION['session_token'] = $sessionToken;
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "Invalid or expired code.";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['resend'])) {
        if (!isset($_SESSION['email'])) {
            $message = "Session expired. Please try logging in again.";
        } else {
            $email = $_SESSION['email'];
            $code = rand(100000, 999999);

            $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE email = ?");
            $stmt->bind_param("ss", $code, $email);
            $stmt->execute();
            $stmt->close();

            // Send email using PHPMailer
            require 'PHPMailer/src/PHPMailer.php';
            require 'PHPMailer/src/SMTP.php';
            require 'PHPMailer/src/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('GMAIL_USERNAME'); // From .env
            $mail->Password = getenv('GMAIL_APP_PASSWORD'); // From .env
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom(getenv('GMAIL_USERNAME'), 'Your App');
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = "New Verification Code";
            $mail->Body = "Your new verification code is: $code";

            if ($mail->send()) {
                $message = "New code sent to your email.";
            } else {
                $message = "Failed to send email.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Your App</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            0% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(102, 126, 234, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(102, 126, 234, 0);
            }
        }

        .verification-icon::before {
            content: "✉";
            font-size: 36px;
            color: white;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.5;
        }

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

        .message.error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .message.success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .message.info {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            color: #555;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

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

        input[type="text"]::placeholder {
            color: #999;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

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

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .resend-info {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon"></div>
        <h1>Verify Your Email</h1>
        <p class="subtitle">We've sent a verification code to your email address. Please enter it below to complete your registration.</p>
        
        <?php if ($message): ?>
            <div class="message <?php 
                echo strpos($message, 'expired') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'Failed') !== false || strpos($message, 'not found') !== false ? 'error' : 
                     (strpos($message, 'sent') !== false ? 'success' : 'info'); 
            ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" id="verificationForm">
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input type="text" id="code" name="code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            
            <div class="button-group">
                <button type="submit" name="verify" class="btn-primary">Verify</button>
                <button type="submit" name="resend" class="btn-secondary">Resend Code</button>
            </div>
        </form>
        
        <p class="resend-info">Didn't receive the code? Check your spam folder or click "Resend Code"</p>
    </div>

    <script>
        // Auto-format verification code input
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });

        // Add focus to input on page load
        window.addEventListener('load', function() {
            document.getElementById('code').focus();
        });
    </script>
</body>
</html>