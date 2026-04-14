<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['session_token'])) {
    echo json_encode(['valid' => false]);
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
    session_destroy();
    echo json_encode(['valid' => false]);
    exit();
}

echo json_encode(['valid' => true]);
