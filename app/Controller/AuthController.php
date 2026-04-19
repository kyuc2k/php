<?php

require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../config/google_oauth.php';

class AuthController {
    private $userModel;
    private $googleOAuth;

    public function __construct() {
        session_start();
        $this->userModel = new User();
        $this->googleOAuth = new GoogleOAuth();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                $_SESSION['user'] = $user;
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
                // Auto-login after successful registration
                $user = $this->userModel->getByEmail($email);
                $_SESSION['user'] = $user;
                header('Location: /dashboard');
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
            // Update existing user info
            $this->userModel->updateGoogleUser($googleId, $email, $name, $picture);
            $user = $this->userModel->getByGoogleId($googleId);
            error_log('Google OAuth: Updated existing user');
        } else {
            // Create new user
            $this->userModel->createGoogleUser($googleId, $email, $name, $picture);
            $user = $this->userModel->getByGoogleId($googleId);
            error_log('Google OAuth: Created new user');
        }

        $_SESSION['user'] = $user;
        header('Location: /dashboard');
        exit;
    }

    public function logout() {
        session_destroy();
        header('Location: /');
        exit;
    }
}
