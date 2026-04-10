<?php
session_start();

// Kiểm tra nếu chưa login thì chuyển hướng sang login.php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Nếu đã login, chuyển hướng sang dashboard.php
header("Location: dashboard.php");
exit();

