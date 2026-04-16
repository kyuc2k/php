<?php

class AuthController
{
    private mysqli $conn;
    private User $userModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->userModel = new User($conn);
    }

    public function login(): void
    {
        Auth::redirectIfAuthenticated();

        global $client_id, $redirect_uri;

        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        $google_login_url = "https://accounts.google.com/o/oauth2/auth?"
            . "client_id=" . $client_id
            . "&redirect_uri=" . $redirect_uri
            . "&response_type=code"
            . "&scope=email profile"
            . "&access_type=online";

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
            $email    = $_POST['email'];
            $password = $_POST['password'];

            $row = $this->userModel->findByEmail($email);
            if ($row) {
                if (password_verify($password, $row['password'])) {
                    if ($row['email_verified'] == 1) {
                        Auth::loginUser($this->conn, [
                            'id'    => $row['id'],
                            'name'  => $row['name'],
                            'email' => $email,
                        ]);
                        ActivityLogger::log($this->conn, $row['id'], 'login', 'Đăng nhập bằng email');
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $_SESSION['email'] = $email;
                        $message = "Please verify your email before logging in. A verification code has been sent to your email.";

                        $code = rand(100000, 999999);
                        $this->userModel->updateVerificationCode($email, $code);
                        Mailer::send($email, '', 'Email Verification Code', "Your verification code is: $code");
                        header("Location: verify.php");
                        exit();
                    }
                } else {
                    $message = "Invalid password.";
                    ActivityLogger::log($this->conn, $row['id'], 'login_failed', 'Sai mật khẩu - Email: ' . $email);
                }
            } else {
                $message = "User not found.";
                ActivityLogger::log($this->conn, null, 'login_failed', 'Email không tồn tại: ' . $email);
            }
        }

        view('auth/login', compact('message', 'google_login_url'));
    }

    public function register(): void
    {
        Auth::redirectIfAuthenticated();

        global $client_id, $redirect_uri;

        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        $google_login_url = "https://accounts.google.com/o/oauth2/auth?"
            . "client_id=" . $client_id
            . "&redirect_uri=" . $redirect_uri
            . "&response_type=code"
            . "&scope=email profile"
            . "&access_type=online";

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
            $name       = $_POST['name'];
            $email      = $_POST['email'];
            $password   = $_POST['password'];
            $repassword = $_POST['repassword'];

            if ($this->userModel->emailExists($email)) {
                $message = "Email already registered.";
            } elseif ($password !== $repassword) {
                $message = "Passwords do not match.";
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $code = rand(100000, 999999);

                $newUserId = $this->userModel->create([
                    'name'              => $name,
                    'email'             => $email,
                    'password'          => $passwordHash,
                    'verification_code' => $code,
                ]);

                ActivityLogger::log($this->conn, $newUserId, 'signup', 'Đăng ký tài khoản - ' . $email);

                if (Mailer::send($email, $name, 'Email Verification Code', "Your verification code is: $code")) {
                    $_SESSION['email'] = $email;
                    header("Location: verify.php");
                    exit();
                } else {
                    $message = "Failed to send verification email.";
                }
            }
        }

        view('auth/register', compact('message', 'google_login_url'));
    }

    public function verify(): void
    {
        $message = '';

        if (!isset($_SESSION['email']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
            $message = "Please complete the registration process first.";
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['verify'])) {
                $code = trim($_POST['code']);
                if (empty($code)) {
                    $message = "Please enter the verification code.";
                } elseif (!isset($_SESSION['email'])) {
                    $message = "Session expired. Please try logging in again.";
                } else {
                    $email = $_SESSION['email'];
                    $row = $this->userModel->verifyCode($email, $code);
                    if ($row) {
                        $this->userModel->verifyEmail($email);
                        $userInfo = $this->userModel->findByEmailAny($email);
                        Auth::loginUser($this->conn, [
                            'id'    => $userInfo['id'],
                            'name'  => $userInfo['name'],
                            'email' => $email,
                        ]);
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $message = "Invalid or expired code.";
                    }
                }
            } elseif (isset($_POST['resend'])) {
                if (!isset($_SESSION['email'])) {
                    $message = "Session expired. Please try logging in again.";
                } else {
                    $email = $_SESSION['email'];
                    $code = rand(100000, 999999);
                    $this->userModel->updateVerificationCode($email, $code);
                    if (Mailer::send($email, '', 'New Verification Code', "Your new verification code is: $code")) {
                        $message = "New code sent to your email.";
                    } else {
                        $message = "Failed to send email.";
                    }
                }
            }
        }

        view('auth/verify', compact('message'));
    }

    public function forgotPassword(): void
    {
        Auth::redirectIfAuthenticated();

        $message     = '';
        $messageType = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $user  = $this->userModel->findByEmail($email);

            if ($user) {
                $code = rand(100000, 999999);
                $this->userModel->updateVerificationCode($email, $code, 10);

                $body = "Xin chào " . $user['name'] . ",\n\nMã đặt lại mật khẩu của bạn là: $code\n\nMã này có hiệu lực trong 10 phút.\n\nNếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.";
                if (Mailer::send($email, $user['name'], 'Reset Password - PDF Manager', $body)) {
                    ActivityLogger::log($this->conn, $user['id'], 'forgot_password', 'Yêu cầu đặt lại mật khẩu - ' . $email);
                    $_SESSION['reset_email'] = $email;
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $message = "Không thể gửi email. Vui lòng thử lại.";
                    $messageType = "error";
                }
            } else {
                $message = "Email không tồn tại hoặc tài khoản được đăng ký bằng Google.";
                $messageType = "error";
            }
        }

        view('auth/forgot_password', compact('message', 'messageType'));
    }

    public function resetPassword(): void
    {
        Auth::redirectIfAuthenticated();

        if (!isset($_SESSION['reset_email'])) {
            header("Location: forgot_password.php");
            exit();
        }

        $email       = $_SESSION['reset_email'];
        $message     = '';
        $messageType = '';
        $step        = isset($_SESSION['reset_verified']) ? 'new_password' : 'verify';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['verify_code'])) {
                $code = trim($_POST['code'] ?? '');
                $row  = $this->userModel->verifyCode($email, $code);
                if ($row) {
                    $_SESSION['reset_verified'] = true;
                    $step = 'new_password';
                } else {
                    $message = "Mã xác nhận không đúng hoặc đã hết hạn.";
                    $messageType = "error";
                }
            } elseif (isset($_POST['reset_password'])) {
                $newPassword     = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (strlen($newPassword) < 6) {
                    $message = "Mật khẩu phải có ít nhất 6 ký tự.";
                    $messageType = "error";
                } elseif ($newPassword !== $confirmPassword) {
                    $message = "Mật khẩu xác nhận không khớp.";
                    $messageType = "error";
                } else {
                    $this->userModel->updatePasswordByEmail($email, password_hash($newPassword, PASSWORD_DEFAULT));

                    $userInfo = $this->userModel->findByEmailAny($email);
                    if ($userInfo) {
                        ActivityLogger::log($this->conn, $userInfo['id'], 'reset_password', 'Đặt lại mật khẩu thành công');
                    }

                    unset($_SESSION['reset_email'], $_SESSION['reset_verified']);
                    $_SESSION['message'] = "Đặt lại mật khẩu thành công! Hãy đăng nhập với mật khẩu mới.";
                    header("Location: login.php");
                    exit();
                }
            } elseif (isset($_POST['resend'])) {
                $code = rand(100000, 999999);
                $this->userModel->updateVerificationCode($email, $code, 10);
                if (Mailer::send($email, '', 'Reset Password - PDF Manager', "Mã đặt lại mật khẩu mới: $code\n\nMã này có hiệu lực trong 10 phút.")) {
                    $message = "Đã gửi lại mã xác nhận.";
                    $messageType = "success";
                } else {
                    $message = "Không thể gửi email.";
                    $messageType = "error";
                }
            }
        }

        view('auth/reset_password', compact('email', 'message', 'messageType', 'step'));
    }

    public function changePassword(): void
    {
        Auth::check($this->conn);

        $user        = Auth::user();
        $message     = '';
        $messageType = '';

        $userRow     = $this->userModel->findById($user['id']);
        $hasPassword = !empty($userRow['password']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($hasPassword) {
                $currentPassword = $_POST['current_password'] ?? '';
                if (!password_verify($currentPassword, $userRow['password'])) {
                    $message = "Mật khẩu hiện tại không đúng.";
                    $messageType = "error";
                } elseif (strlen($newPassword) < 6) {
                    $message = "Mật khẩu mới phải có ít nhất 6 ký tự.";
                    $messageType = "error";
                } elseif ($newPassword !== $confirmPassword) {
                    $message = "Mật khẩu xác nhận không khớp.";
                    $messageType = "error";
                } elseif (password_verify($newPassword, $userRow['password'])) {
                    $message = "Mật khẩu mới không được trùng với mật khẩu hiện tại.";
                    $messageType = "error";
                } else {
                    $this->userModel->updatePassword($user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
                    ActivityLogger::log($this->conn, $user['id'], 'change_password', 'Đổi mật khẩu thành công');
                    $_SESSION['message'] = "Đổi mật khẩu thành công!";
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                if (strlen($newPassword) < 6) {
                    $message = "Mật khẩu phải có ít nhất 6 ký tự.";
                    $messageType = "error";
                } elseif ($newPassword !== $confirmPassword) {
                    $message = "Mật khẩu xác nhận không khớp.";
                    $messageType = "error";
                } else {
                    $this->userModel->updatePassword($user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
                    ActivityLogger::log($this->conn, $user['id'], 'change_password', 'Tạo mật khẩu mới (Google user)');
                    $_SESSION['message'] = "Tạo mật khẩu thành công! Bạn có thể dùng email và mật khẩu để đăng nhập.";
                    header("Location: dashboard.php");
                    exit();
                }
            }
        }

        view('auth/change_password', compact('user', 'message', 'messageType', 'hasPassword'));
    }

    public function googleCallback(): void
    {
        global $client_id, $client_secret, $redirect_uri;

        if (!isset($_GET['code'])) {
            header("Location: login.php");
            exit();
        }

        $code = $_GET['code'];
        $data = [
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code'
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result   = file_get_contents("https://oauth2.googleapis.com/token", false, $context);
        $response = json_decode($result, true);

        if (!isset($response['access_token'])) {
            header("Location: login.php");
            exit();
        }

        $userInfo = json_decode(file_get_contents(
            "https://www.googleapis.com/oauth2/v1/userinfo?access_token=" . $response['access_token']
        ), true);

        if (!isset($userInfo['id'])) {
            header("Location: login.php");
            exit();
        }

        $googleId = $userInfo['id'];
        $name     = $userInfo['name'];
        $email    = $userInfo['email'];
        $avatar   = $userInfo['picture'];

        $existing = $this->userModel->findByGoogleIdOrEmail($googleId, $email);

        if (!$existing) {
            $userId = $this->userModel->create([
                'google_id' => $googleId,
                'name'      => $name,
                'email'     => $email,
                'avatar'    => $avatar,
            ]);
            ActivityLogger::log($this->conn, $userId, 'signup_google', 'Đăng ký bằng Google - ' . $email);
        } else {
            $userId = $existing['id'];
            if (empty($existing['google_id'])) {
                $this->userModel->linkGoogle($userId, $googleId, $name, $avatar);
            }
        }

        Auth::loginUser($this->conn, [
            'id'        => $userId,
            'google_id' => $googleId,
            'name'      => $name,
            'email'     => $email,
            'picture'   => $avatar,
        ]);

        ActivityLogger::log($this->conn, $userId, 'login_google', 'Đăng nhập bằng Google - ' . $email);
        header("Location: dashboard.php");
        exit();
    }

    public function logout(): void
    {
        if (isset($_SESSION['user']['id'])) {
            $this->userModel->updateSessionToken($_SESSION['user']['id'], null);
        }
        session_destroy();
        header("Location: index.php");
        exit();
    }

    public function checkSession(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user']) || !isset($_SESSION['session_token'])) {
            echo json_encode(['valid' => false]);
            exit();
        }

        $dbToken = $this->userModel->getSessionToken($_SESSION['user']['id']);

        if (!$dbToken || $dbToken !== $_SESSION['session_token']) {
            session_destroy();
            echo json_encode(['valid' => false]);
            exit();
        }

        echo json_encode(['valid' => true]);
    }
}
