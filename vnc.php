<?php
session_start();
require 'config.php';

$token = $_GET['token'] ?? null;

if (!$token) {
    die("Missing token");
}

$res = $conn->query("
    SELECT * FROM vm_sessions
    WHERE token='$token'
    AND expires_at > NOW()
");

if ($res->num_rows == 0) {
    die("Invalid or expired session");
}

$_SESSION['vnc_token'] = $token;

// redirect sang noVNC
header("Location: /vnc_gate.php?token=$token");
exit;