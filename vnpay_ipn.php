<?php
/**
 * VNPay IPN (Instant Payment Notification) Handler
 * VNPay gọi URL này sau khi user thanh toán thành công.
 * Xác minh checksum, cập nhật payment status, nâng cấp user.
 */

require 'config.php';

$vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';

// Read GET params
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_Amount = $_GET['vnp_Amount'] ?? 0;
$vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
$vnp_PayDate = $_GET['vnp_PayDate'] ?? '';
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';

// Log IPN for debugging
$logData = date('[Y-m-d H:i:s] ') . json_encode($_GET) . "\n";
file_put_contents(__DIR__ . '/vnpay_ipn.log', $logData, FILE_APPEND);

// Build checksum data
$inputData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_" && $key != 'vnp_SecureHash' && $key != 'vnp_SecureHashType') {
        $inputData[$key] = $value;
    }
}

ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$expectedSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

// Verify checksum
if ($vnp_SecureHash !== $expectedSecureHash) {
    file_put_contents(__DIR__ . '/vnpay_ipn.log', date('[Y-m-d H:i:s] ') . "SIGNATURE MISMATCH\n", FILE_APPEND);
    echo json_encode(['RspCode' => '97', 'Message' => 'Invalid checksum']);
    exit();
}

// Check response code (00 = success)
if ($vnp_ResponseCode == '00') {
    // Find pending payment
    $stmt = $conn->prepare("SELECT id, user_id, plan, storage_bytes, status FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $vnp_TxnRef);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($payment && $payment['status'] === 'pending') {
        // Update payment to completed
        $stmt_up = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ?");
        $stmt_up->bind_param("si", $vnp_TransactionNo, $payment['id']);
        $stmt_up->execute();
        $stmt_up->close();

        // Upgrade user storage
        $stmt_user = $conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
        $stmt_user->bind_param("ii", $payment['storage_bytes'], $payment['user_id']);
        $stmt_user->execute();
        $stmt_user->close();

        file_put_contents(__DIR__ . '/vnpay_ipn.log', date('[Y-m-d H:i:s] ') . "SUCCESS: Payment {$payment['id']} upgraded user {$payment['user_id']} to {$payment['plan']}\n", FILE_APPEND);
    }

    echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
} else {
    // Payment failed
    file_put_contents(__DIR__ . '/vnpay_ipn.log', date('[Y-m-d H:i:s] ') . "FAILED: Order {$vnp_TxnRef} ResponseCode={$vnp_ResponseCode}\n", FILE_APPEND);
    echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
}
