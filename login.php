<?php

require 'config.php';

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);

$google_login_url = "https://accounts.google.com/o/oauth2/auth?"
    . "client_id=".$client_id
    . "&redirect_uri=".$redirect_uri
    . "&response_type=code"
    . "&scope=email profile"
    . "&access_type=online";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $code = rand(100000, 999999);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_code, email_verified, verification_code_expires) VALUES (?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
        $stmt->bind_param("ssss", $name, $email, $password, $code);
        if ($stmt->execute()) {
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
            $mail->Subject = "Verification Code";
            $mail->Body = "Your verification code is: $code";

            if ($mail->send()) {
                $_SESSION['email'] = $email;
                header("Location: verify.php");
                exit();
            } else {
                $message = "Failed to send email.";
            }
        } else {
            $message = "Registration failed.";
        }
        $stmt->close();
    } elseif (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, name, password, email_verified FROM users WHERE email = ? AND password IS NOT NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password']) && $row['email_verified'] == 1) {
                $_SESSION['user'] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'email' => $email,
                ];
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Invalid credentials or email not verified.";
            }
        } else {
            $message = "User not found.";
        }
        $stmt->close();
    }
}

?>

<?php if ($message): ?>
<p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<h2>Login</h2>
<form method="post">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
</form>

<h2>Register</h2>
<form method="post">
    <input type="text" name="name" placeholder="Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="register">Register</button>
</form>

<a href="<?= $google_login_url ?>">
Login/Register with Google
</a>