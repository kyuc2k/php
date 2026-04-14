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
                    
                    $_SESSION['user'] = [
                        'id' => $user_row['id'],
                        'name' => $user_row['name'],
                        'email' => $email,
                    ];
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

<?php if ($message): ?>
<p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post">
    <input type="text" name="code" placeholder="Enter verification code">
    <button type="submit" name="verify">Verify</button>
    <button type="submit" name="resend">Resend Code</button>
</form>