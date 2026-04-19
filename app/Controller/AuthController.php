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
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->authenticate($username, $password);

            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: /dashboard');
                exit;
            } else {
                $error = 'Invalid username or password';
                require __DIR__ . '/../View/login.php';
                return;
            }
        }
        require __DIR__ . '/../View/login.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate input
            if (empty($username) || empty($email) || empty($password)) {
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

            // Check if username or email already exists
            $existingUser = $this->userModel->getByUsername($username);
            if ($existingUser) {
                $error = 'Username đã tồn tại';
                require __DIR__ . '/../View/register.php';
                return;
            }

            $existingEmail = $this->userModel->getByEmail($email);
            if ($existingEmail) {
                $error = 'Email đã được sử dụng';
                require __DIR__ . '/../View/register.php';
                return;
            }

            // Create user
            $result = $this->userModel->createWithEmail($username, $email, $password);
            
            if ($result) {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
                require __DIR__ . '/../View/register.php';
                return;
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
            header('Location: /login?error=google_auth_failed');
            exit;
        }

        $code = $_GET['code'];
        $tokenData = $this->googleOAuth->getAccessToken($code);

        if (isset($tokenData['error'])) {
            header('Location: /login?error=token_error');
            exit;
        }

        $accessToken = $tokenData['access_token'];
        $userInfo = $this->googleOAuth->getUserInfo($accessToken);

        if (!isset($userInfo['id'])) {
            header('Location: /login?error=user_info_error');
            exit;
        }

        $googleId = $userInfo['id'];
        $email = $userInfo['email'] ?? '';
        $name = $userInfo['name'] ?? '';
        $picture = $userInfo['picture'] ?? '';

        $user = $this->userModel->getByGoogleId($googleId);

        if ($user) {
            // Update existing user info
            $this->userModel->updateGoogleUser($googleId, $email, $name, $picture);
            $user = $this->userModel->getByGoogleId($googleId);
        } else {
            // Create new user
            $this->userModel->createGoogleUser($googleId, $email, $name, $picture);
            $user = $this->userModel->getByGoogleId($googleId);
        }

        $_SESSION['user'] = $user;
        header('Location: /dashboard');
        exit;
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
