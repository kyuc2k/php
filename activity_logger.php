<?php
/**
 * Activity Logger - ghi lại các hoạt động của user.
 * Tự động tạo bảng activity_logs nếu chưa tồn tại.
 *
 * Các action types:
 *   login, login_google, signup, signup_google,
 *   change_password, forgot_password, reset_password,
 *   upload_file, delete_file, upgrade_plan
 */

function log_activity(mysqli $conn, ?int $userId, string $action, string $details = '', ?string $ip = null): void
{
    static $tableChecked = false;

    if (!$tableChecked) {
        $conn->query("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id          INT AUTO_INCREMENT PRIMARY KEY,
                user_id     INT NULL,
                action      VARCHAR(50)  NOT NULL,
                details     TEXT         NULL,
                ip_address  VARCHAR(45)  NULL,
                created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user  (user_id),
                INDEX idx_action (action),
                INDEX idx_time  (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $tableChecked = true;
    }

    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');

    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
