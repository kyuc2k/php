<?php
/**
 * MoMo IPN (Instant Payment Notification) Handler
 * MoMo gọi URL này sau khi user thanh toán thành công.
 * Xác minh signature, cập nhật payment status, nâng cấp user.
 */

require 'config.php';

// Read raw POST body
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

// Log IPN for debugging
file_put_contents(__DIR__ . '/momo_ipn.log', date('[Y-m-d H:i:s] ') . $rawBody . "\n", FILE_APPEND);

if (!$data) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid request']);
    exit();
}

// MoMo API credentials
$accessKey = getenv('MOMO_ACCESS_KEY') ?: 'klm05TvNBzhg7h7j';
$secretKey = getenv('MOMO_SECRET_KEY') ?: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

// Extract fields from MoMo callback
$partnerCode  = $data['partnerCode'] ?? '';
$orderId      = $data['orderId'] ?? '';
$requestId    = $data['requestId'] ?? '';
$amount       = $data['amount'] ?? 0;
$orderInfo    = $data['orderInfo'] ?? '';
$orderType    = $data['orderType'] ?? '';
$transId      = $data['transId'] ?? '';
$resultCode   = $data['resultCode'] ?? -1;
$message      = $data['message'] ?? '';
$payType      = $data['payType'] ?? '';
$responseTime = $data['responseTime'] ?? '';
$extraData    = $data['extraData'] ?? '';
$signature    = $data['signature'] ?? '';

// Verify signature
$rawSignature = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&message={$message}&orderId={$orderId}&orderInfo={$orderInfo}&orderType={$orderType}&partnerCode={$partnerCode}&payType={$payType}&requestId={$requestId}&responseTime={$responseTime}&resultCode={$resultCode}&transId={$transId}";
$expectedSignature = hash_hmac('sha256', $rawSignature, $secretKey);

if ($signature !== $expectedSignature) {
    file_put_contents(__DIR__ . '/momo_ipn.log', date('[Y-m-d H:i:s] ') . "SIGNATURE MISMATCH\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['message' => 'Invalid signature']);
    exit();
}

// resultCode == 0 means payment successful
if ($resultCode == 0) {
    // Find pending payment
    $stmt = $conn->prepare("SELECT id, user_id, plan, storage_bytes, status FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($payment && $payment['status'] === 'pending') {
        // Update payment to completed
        $stmt_up = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ?");
        $stmt_up->bind_param("si", $transId, $payment['id']);
        $stmt_up->execute();
        $stmt_up->close();

        // Upgrade user storage
        $stmt_user = $conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
        $stmt_user->bind_param("ii", $payment['storage_bytes'], $payment['user_id']);
        $stmt_user->execute();
        $stmt_user->close();

        file_put_contents(__DIR__ . '/momo_ipn.log', date('[Y-m-d H:i:s] ') . "SUCCESS: Payment {$payment['id']} upgraded user {$payment['user_id']} to {$payment['plan']}\n", FILE_APPEND);
    }
} else {
    // Payment failed - update status
    $stmt = $conn->prepare("UPDATE payments SET status = 'failed' WHERE order_id = ?");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $stmt->close();

    file_put_contents(__DIR__ . '/momo_ipn.log', date('[Y-m-d H:i:s] ') . "FAILED: Order {$orderId} resultCode={$resultCode}\n", FILE_APPEND);
}

// Respond to MoMo
http_response_code(204);
