<?php

session_start();
require 'config.php';

// Clear session token from DB
if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $stmt->close();
}

session_destroy();

header("Location: index.php");