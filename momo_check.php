<?php
/**
 * Payment Status Check API
 * Frontend polls this endpoint to check if payment has been confirmed.
 */

session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$orderId = $_GET['order_id'] ?? '';
$userId = $_SESSION['user']['id'] ?? 0;

if (empty($orderId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
    exit();
}

$stmt = $conn->prepare("SELECT status, transaction_id FROM payments WHERE order_id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("si", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['status' => 'not_found']);
} else {
    echo json_encode([
        'status' => $payment['status'],
        'transaction_id' => $payment['transaction_id'],
    ]);
}
