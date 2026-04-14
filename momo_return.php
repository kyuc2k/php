<?php
/**
 * MoMo Return URL Handler
 * User được redirect về đây sau khi thanh toán trên MoMo.
 * Xác minh signature, hiển thị kết quả.
 */

session_start();
require 'config.php';

$accessKey = getenv('MOMO_ACCESS_KEY') ?: 'klm05TvNBzhg7h7j';
$secretKey = getenv('MOMO_SECRET_KEY') ?: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

// MoMo sends these params via GET
$partnerCode  = $_GET['partnerCode'] ?? '';
$orderId      = $_GET['orderId'] ?? '';
$requestId    = $_GET['requestId'] ?? '';
$amount       = $_GET['amount'] ?? 0;
$orderInfo    = $_GET['orderInfo'] ?? '';
$orderType    = $_GET['orderType'] ?? '';
$transId      = $_GET['transId'] ?? '';
$resultCode   = $_GET['resultCode'] ?? -1;
$message      = $_GET['message'] ?? '';
$payType      = $_GET['payType'] ?? '';
$responseTime = $_GET['responseTime'] ?? '';
$extraData    = $_GET['extraData'] ?? '';
$signature    = $_GET['signature'] ?? '';

// Verify signature
$rawSignature = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&message={$message}&orderId={$orderId}&orderInfo={$orderInfo}&orderType={$orderType}&partnerCode={$partnerCode}&payType={$payType}&requestId={$requestId}&responseTime={$responseTime}&resultCode={$resultCode}&transId={$transId}";
$expectedSignature = hash_hmac('sha256', $rawSignature, $secretKey);

if ($signature !== $expectedSignature) {
    $_SESSION['upgrade_message'] = 'Xác minh giao dịch thất bại. Vui lòng liên hệ hỗ trợ.';
    $_SESSION['upgrade_message_type'] = 'error';
    header("Location: upgrade.php");
    exit();
}

if ($resultCode == 0) {
    // Payment successful - IPN should have already upgraded the user
    // But double-check and upgrade if IPN hasn't processed yet
    $stmt = $conn->prepare("SELECT id, user_id, plan, storage_bytes, status FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($payment && $payment['status'] === 'pending') {
        // IPN hasn't arrived yet, process here
        $stmt_up = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ?");
        $stmt_up->bind_param("si", $transId, $payment['id']);
        $stmt_up->execute();
        $stmt_up->close();

        $stmt_user = $conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
        $stmt_user->bind_param("ii", $payment['storage_bytes'], $payment['user_id']);
        $stmt_user->execute();
        $stmt_user->close();
    }

    // Decode extraData to get plan info
    $extra = json_decode(base64_decode($extraData), true);
    $plans = [
        '1gb' => '1GB',
        '2gb' => '2GB',
    ];
    $planLabel = $plans[$extra['plan'] ?? ''] ?? '';

    $_SESSION['upgrade_message'] = 'Thanh toán thành công! Đã nâng cấp lên gói ' . $planLabel . '.';
    $_SESSION['upgrade_message_type'] = 'success';
} else {
    $_SESSION['upgrade_message'] = 'Thanh toán không thành công. ' . htmlspecialchars($message);
    $_SESSION['upgrade_message_type'] = 'error';
}

header("Location: upgrade.php");
exit();
