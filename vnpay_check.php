<?php
/**
 * VNPay Payment Status Check API
 * Frontend polls this endpoint to check if payment has been confirmed.
 * If DB status is still 'pending', actively queries VNPay API to check real status.
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

$stmt = $conn->prepare("SELECT id, status, transaction_id, plan, storage_bytes FROM payments WHERE order_id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("si", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    echo json_encode(['status' => 'not_found']);
    exit();
}

// If already completed, return immediately
if ($payment['status'] === 'completed') {
    echo json_encode([
        'status' => $payment['status'],
        'transaction_id' => $payment['transaction_id'],
    ]);
    exit();
}

// --- Status is 'pending' or 'failed' → query VNPay API to check real status ---
$vnp_TmnCode = getenv('VNP_TMN_CODE') ?: 'YOUR_VNPAY_TMN_CODE';
$vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';
$vnp_ApiUrl = getenv('VNP_API_URL') ?: 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';

// Build query request
$vnp_RequestId = time() . rand(1000, 9999);
$vnp_Command = 'querydr';
$vnp_TxnRef = $orderId;
$vnp_OrderInfo = 'Query transaction status';
$vnp_TransactionDate = date('YmdHis');
$vnp_CreateDate = date('YmdHis');
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$inputData = [
    "vnp_RequestId" => $vnp_RequestId,
    "vnp_Version" => "2.1.0",
    "vnp_Command" => $vnp_Command,
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_TxnRef" => $vnp_TxnRef,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_TransactionDate" => $vnp_TransactionDate,
    "vnp_CreateDate" => $vnp_CreateDate,
    "vnp_IpAddr" => $vnp_IpAddr,
];

// Build checksum
ksort($inputData);
$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$inputData['vnp_SecureHash'] = $vnp_SecureHash;

// Call VNPay API
$ch = curl_init($vnp_ApiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($inputData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'status' => $payment['status'],
        'transaction_id' => $payment['transaction_id'],
    ]);
    exit();
}

$vnpResult = json_decode($response, true);

// Check if transaction is successful (vnp_ResponseCode = 00)
if ($vnpResult && isset($vnpResult['vnp_ResponseCode']) && $vnpResult['vnp_ResponseCode'] == '00') {
    $transId = $vnpResult['vnp_TransactionNo'] ?? '';

    // Update payment to completed
    $stmt_up = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ?, completed_at = NOW() WHERE id = ? AND status != 'completed'");
    $stmt_up->bind_param("si", $transId, $payment['id']);
    $stmt_up->execute();
    $stmt_up->close();

    // Upgrade user storage
    $stmt_user = $conn->prepare("UPDATE users SET storage_limit = ? WHERE id = ?");
    $stmt_user->bind_param("ii", $payment['storage_bytes'], $userId);
    $stmt_user->execute();
    $stmt_user->close();

    echo json_encode([
        'status' => 'completed',
        'transaction_id' => $transId,
    ]);
    exit();
}

// Still pending or other status
echo json_encode([
    'status' => $payment['status'],
    'transaction_id' => $payment['transaction_id'],
]);
