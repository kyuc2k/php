<?php
/**
 * Auth check - validates session token against DB.
 * Include this file on every protected page AFTER session_start() and require 'config.php'.
 * If another device logs in, this session becomes invalid.
 */

// Ensure session_token column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'session_token'");

if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD session_token VARCHAR(64) DEFAULT NULL");
}

if (!isset($_SESSION['user']) || !isset($_SESSION['session_token'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$sessionToken = $_SESSION['session_token'];

$stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row || $row['session_token'] !== $sessionToken) {
    // Another device has logged in - destroy this session
    session_destroy();
    session_start();
    $_SESSION['message'] = 'Tài khoản của bạn đã được đăng nhập trên một thiết bị khác. Phiên này đã bị đăng xuất.';
    header("Location: login.php");
    exit();
}
