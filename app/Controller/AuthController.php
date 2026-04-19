<?php

require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/UserLog.php';
require_once __DIR__ . '/../config/google_oauth.php';

class AuthController {
    private $userModel;
    private $googleOAuth;
    private $userLog;

    public function __construct() {
        session_start();
        $this->userModel = new User();
        $this->googleOAuth = new GoogleOAuth();
        $this->userLog = new UserLog();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                // Check if email is verified (except for Google users)
                if (empty($user['google_id']) && $user['email_verified'] == 0) {
                    $error = 'Email chưa được xác nhận. Vui lòng kiểm tra email để xác nhận tài khoản.';
                    require __DIR__ . '/../View/login.php';
                    return;
                }
                
                // Generate new session ID
                $newSessionId = bin2hex(random_bytes(32));
                
                // Check if user has existing session from different device
                $oldSessionId = $user['session_id'] ?? null;
                $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                
                if ($oldSessionId && $oldSessionId !== session_id()) {
                    // Log that user logged in from new device
                    $this->userLog->create($user['id'], 'NEW_DEVICE_LOGIN', 'User logged in from new device, old session invalidated', $currentIp);
                }
                
                // Update session ID in database
                $this->userModel->updateSessionId($user['id'], $newSessionId);
                
                // Store session ID in session
                $_SESSION['session_id'] = $newSessionId;
                $_SESSION['user'] = $user;
                
                $this->userLog->create($user['id'], 'LOGIN', 'User logged in', $currentIp);
                header('Location: /dashboard');
                exit;
            } else {
                $error = 'Invalid email or password';
                require __DIR__ . '/../View/login.php';
                return;
            }
        }
        require __DIR__ . '/../View/login.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate input
            if (empty($name) || empty($email) || empty($password)) {
                $error = 'Vui lòng điền đầy đủ thông tin';
                require __DIR__ . '/../View/register.php';
                return;
            }

            if ($password !== $confirmPassword) {
                $error = 'Mật khẩu xác nhận không khớp';
                require __DIR__ . '/../View/register.php';
                return;
            }

            if (strlen($password) < 8) {
                $error = 'Mật khẩu phải có ít nhất 8 ký tự';
                require __DIR__ . '/../View/register.php';
                return;
            }

            // Check password complexity
            if (!preg_match('/[A-Z]/', $password)) {
                $error = 'Mật khẩu phải có ít nhất 1 chữ hoa';
                require __DIR__ . '/../View/register.php';
                return;
            }

            if (!preg_match('/[a-z]/', $password)) {
                $error = 'Mật khẩu phải có ít nhất 1 chữ thường';
                require __DIR__ . '/../View/register.php';
                return;
            }

            if (!preg_match('/[0-9]/', $password)) {
                $error = 'Mật khẩu phải có ít nhất 1 số';
                require __DIR__ . '/../View/register.php';
                return;
            }

            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $error = 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt (!@#$%^&*...)';
                require __DIR__ . '/../View/register.php';
                return;
            }

            // Check if email already exists
            $existingEmail = $this->userModel->getByEmail($email);
            if ($existingEmail) {
                $error = 'Email đã được sử dụng';
                require __DIR__ . '/../View/register.php';
                return;
            }

            // Create user with name and email
            $result = $this->userModel->createWithEmail($name, $email, $password);
            
            if ($result) {
                // Get verification code
                $user = $this->userModel->getByEmail($email);
                $verificationCode = $user['verification_code'];
                
                // Log registration
                $this->userLog->create($user['id'], 'REGISTER', 'User registered account');
                
                // Send verification email
                $this->sendVerificationEmail($email, $verificationCode);
                
                // Store email in session for resend
                $_SESSION['verify_email'] = $email;
                
                // Redirect to code entry page
                header('Location: /enter-verification-code');
                exit;
            } else {
                $error = 'Đăng ký thất bại. Vui lòng thử lại.';
                require __DIR__ . '/../View/register.php';
                return;
            }
        }
        require __DIR__ . '/../View/register.php';
    }

    public function googleLogin() {
        $authUrl = $this->googleOAuth->getAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback() {
        if (!isset($_GET['code'])) {
            error_log('Google OAuth: No code received');
            header('Location: /login?error=google_auth_failed');
            exit;
        }

        $code = $_GET['code'];
        error_log('Google OAuth: Code received - ' . substr($code, 0, 10) . '...');
        
        $tokenData = $this->googleOAuth->getAccessToken($code);

        if (isset($tokenData['error'])) {
            error_log('Google OAuth: Token error - ' . print_r($tokenData, true));
            header('Location: /login?error=token_error');
            exit;
        }

        $accessToken = $tokenData['access_token'];
        error_log('Google OAuth: Access token received');
        
        $userInfo = $this->googleOAuth->getUserInfo($accessToken);

        if (!isset($userInfo['id'])) {
            error_log('Google OAuth: User info error - ' . print_r($userInfo, true));
            header('Location: /login?error=user_info_error');
            exit;
        }

        $googleId = $userInfo['id'];
        $email = $userInfo['email'] ?? '';
        $name = $userInfo['name'] ?? '';
        $picture = $userInfo['picture'] ?? '';

        error_log('Google OAuth: User info - ID: ' . $googleId . ', Email: ' . $email);

        $user = $this->userModel->getByGoogleId($googleId);

        if ($user) {
            // Update existing user info and auto-verify email
            $this->userModel->updateGoogleUser($googleId, $email, $name, $picture);
            $this->userModel->setGoogleEmailVerified($email);
            $user = $this->userModel->getByGoogleId($googleId);
            error_log('Google OAuth: Updated existing user');
        } else {
            // Create new user with auto-verified email
            $this->userModel->createGoogleUser($googleId, $email, $name, $picture);
            $this->userModel->setGoogleEmailVerified($email);
            $user = $this->userModel->getByGoogleId($googleId);
            error_log('Google OAuth: Created new user');
        }

        // Generate new session ID
        $newSessionId = bin2hex(random_bytes(32));
        
        // Check if user has existing session from different device
        $oldSessionId = $user['session_id'] ?? null;
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        if ($oldSessionId && $oldSessionId !== session_id()) {
            // Log that user logged in from new device
            $this->userLog->create($user['id'], 'NEW_DEVICE_LOGIN', 'User logged in from new device via Google OAuth, old session invalidated', $currentIp);
        }
        
        // Update session ID in database
        $this->userModel->updateSessionId($user['id'], $newSessionId);
        
        // Store session ID in session
        $_SESSION['session_id'] = $newSessionId;
        $_SESSION['user'] = $user;
        $this->userLog->create($user['id'], 'GOOGLE_LOGIN', 'User logged in via Google OAuth', $currentIp);
        header('Location: /dashboard');
        exit;
    }

    public function logout() {
        if (isset($_SESSION['user'])) {
            $this->userLog->create($_SESSION['user']['id'], 'LOGOUT', 'User logged out');
        }
        session_destroy();
        header('Location: /');
        exit;
    }

    private function sendVerificationEmail($email, $verificationCode) {
        require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('GMAIL_USERNAME');
            $mail->Password = getenv('GMAIL_APP_PASSWORD');
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            $mail->setFrom(getenv('GMAIL_USERNAME'), 'VPS Treo Game Java');
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Mã xác nhận tài khoản - VPS Treo Game Java';
            
            $mail->Body = "
                <html>
                <head>
                    <title>Mã xác nhận tài khoản</title>
                </head>
                <body>
                    <h2>Mã xác nhận tài khoản của bạn</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại VPS Treo Game Java.</p>
                    <p>Mã xác nhận của bạn là: <strong>$verificationCode</strong></p>
                    <p>Vui lòng nhập mã này vào trang web để xác nhận tài khoản.</p>
                    <p>Mã này sẽ hết hạn sau 5 phút.</p>
                    <p>Nếu bạn không đăng ký tài khoản này, vui lòng bỏ qua email này.</p>
                </body>
                </html>
            ";
            
            $mail->send();
        } catch (Exception $e) {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        }
    }

    public function verifyEmail() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = $_POST['code'] ?? '';
            
            if (empty($code) || !preg_match('/^[0-9]{6}$/', $code)) {
                $error = 'Mã xác nhận phải là 6 số';
                require __DIR__ . '/../View/enter-verification-code.php';
                return;
            }
            
            $user = $this->userModel->getByVerificationCode($code);
            
            if (!$user) {
                $error = 'Mã xác nhận không hợp lệ hoặc đã hết hạn (5 phút).';
                require __DIR__ . '/../View/enter-verification-code.php';
                return;
            }
            
            $result = $this->userModel->verifyEmail($code);
            
            if ($result) {
                // Clear session email
                unset($_SESSION['verify_email']);
                
                // Auto-login after successful verification
                $user = $this->userModel->getByEmail($user['email']);
                $_SESSION['user'] = $user;
                $this->userLog->create($user['id'], 'EMAIL_VERIFIED', 'User verified email');
                header('Location: /dashboard');
                exit;
            } else {
                $error = 'Xác nhận mã thất bại. Vui lòng thử lại.';
                require __DIR__ . '/../View/enter-verification-code.php';
                return;
            }
        }
        require __DIR__ . '/../View/enter-verification-code.php';
    }

    public function resendVerification() {
        $email = $_SESSION['verify_email'] ?? '';
        
        if (empty($email)) {
            $error = 'Phiên đăng ký đã hết hạn. Vui lòng đăng ký lại.';
            header('Location: /register');
            exit;
        }
        
        $user = $this->userModel->getByEmail($email);
        
        if (!$user) {
            $error = 'Email không tồn tại';
            header('Location: /register');
            exit;
        }
        
        if ($user['email_verified'] == 1) {
            $success = 'Email đã được xác nhận rồi. Bạn có thể đăng nhập ngay.';
            header('Location: /login');
            exit;
        }
        
        // Regenerate verification code
        $this->userModel->regenerateVerificationCode($email);
        $user = $this->userModel->getByEmail($email);
        
        // Send new verification email
        $this->sendVerificationEmail($email, $user['verification_code']);
        
        // Redirect back to code entry page
        header('Location: /enter-verification-code?resent=true');
        exit;
    }

    public function logs() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $logs = $this->userLog->getByUserId($userId);
        require __DIR__ . '/../View/logs.php';
    }

    public function validateSession() {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['valid' => false]);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $currentSessionId = $_SESSION['session_id'] ?? null;
        
        if (!$currentSessionId) {
            echo json_encode(['valid' => false]);
            exit;
        }

        // Check if session matches database
        $user = $this->userModel->getById($userId);
        
        if (!$user || $user['session_id'] !== $currentSessionId) {
            // Session invalid, user logged in from another device
            session_destroy();
            echo json_encode(['valid' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Tài khoản đã được đăng nhập từ thiết bị khác.']);
            exit;
        }

        echo json_encode(['valid' => true]);
        exit;
    }

    public function changePassword() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $userId = $_SESSION['user']['id'];

            // Validate input
            if (empty($currentPassword) || empty($newPassword)) {
                $error = 'Vui lòng điền đầy đủ thông tin';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $error = 'Mật khẩu xác nhận không khớp';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            if (strlen($newPassword) < 8) {
                $error = 'Mật khẩu phải có ít nhất 8 ký tự';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            // Check password complexity
            if (!preg_match('/[A-Z]/', $newPassword)) {
                $error = 'Mật khẩu phải có ít nhất 1 chữ hoa';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            if (!preg_match('/[a-z]/', $newPassword)) {
                $error = 'Mật khẩu phải có ít nhất 1 chữ thường';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            if (!preg_match('/[0-9]/', $newPassword)) {
                $error = 'Mật khẩu phải có ít nhất 1 số';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
                $error = 'Mật khẩu phải có ít nhất 1 ký tự đặc biệt (!@#$%^&*...)';
                require __DIR__ . '/../View/change-password.php';
                return;
            }

            // Change password
            $result = $this->userModel->changePassword($userId, $currentPassword, $newPassword);
            
            if ($result) {
                $this->userLog->create($userId, 'PASSWORD_CHANGED', 'User changed password');
                $success = 'Đổi mật khẩu thành công!';
                require __DIR__ . '/../View/change-password.php';
                return;
            } else {
                $error = 'Mật khẩu hiện tại không đúng';
                require __DIR__ . '/../View/change-password.php';
                return;
            }
        }
        require __DIR__ . '/../View/change-password.php';
    }
}
