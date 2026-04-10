<?php
session_start();

// Kiểm tra nếu chưa login thì chuyển hướng sang login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

echo "Welcome!!!";
