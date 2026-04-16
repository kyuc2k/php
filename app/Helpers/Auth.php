<?php

class Auth
{
    /**
     * Check if user is authenticated with valid session token.
     * Redirects to login if not valid.
     */
    public static function check(mysqli $conn): void
    {
        $userModel = new User($conn);
        $userModel->ensureSessionTokenColumn();

        if (!isset($_SESSION['user']) || !isset($_SESSION['session_token'])) {
            header("Location: login.php");
            exit();
        }

        $userId       = $_SESSION['user']['id'];
        $sessionToken = $_SESSION['session_token'];
        $dbToken      = $userModel->getSessionToken($userId);

        if (!$dbToken || $dbToken !== $sessionToken) {
            session_destroy();
            session_start();
            $_SESSION['message'] = 'Tài khoản của bạn đã được đăng nhập trên một thiết bị khác. Phiên này đã bị đăng xuất.';
            header("Location: login.php");
            exit();
        }
    }

    /**
     * Redirect to dashboard if already logged in.
     */
    public static function redirectIfAuthenticated(): void
    {
        if (isset($_SESSION['user'])) {
            header("Location: dashboard.php");
            exit();
        }
    }

    /**
     * Generate a session token and store in DB + session.
     */
    public static function loginUser(mysqli $conn, array $userData): void
    {
        $userModel    = new User($conn);
        $sessionToken = bin2hex(random_bytes(32));
        $userModel->updateSessionToken($userData['id'], $sessionToken);

        $_SESSION['user']          = $userData;
        $_SESSION['session_token'] = $sessionToken;
    }

    /**
     * Get current user ID or null.
     */
    public static function userId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Get current user array.
     */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
