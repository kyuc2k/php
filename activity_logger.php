<?php
/**
 * Activity Logger - ghi lại các hoạt động của user.
 *
 * Các action types:
 *   login, login_google, signup, signup_google,
 *   change_password, forgot_password, reset_password,
 *   upload_file, delete_file, upgrade_plan
 */

function log_activity(mysqli $conn, ?int $userId, string $action, string $details = '', ?string $ip = null): void
{
    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '');

    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
