<?php
/**
 * Payment Status Check API
 * Frontend polls this endpoint to check if payment has been confirmed.
 * If DB status is still 'pending', actively queries MoMo API to check real status.
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

// --- Status is 'pending' or 'failed' → query MoMo API to check real status ---
$momoEnv     = getenv('MOMO_ENV') ?: 'sandbox';
$partnerCode = getenv('MOMO_PARTNER_CODE') ?: 'MOMOBKUN20180529';
$accessKey   = getenv('MOMO_ACCESS_KEY') ?: 'klm05TvNBzhg7h7j';
$secretKey   = getenv('MOMO_SECRET_KEY') ?: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

if ($momoEnv === 'sandbox') {
    $queryEndpoint = 'https://test-payment.momo.vn/v2/gateway/api/query';
} else {
    $queryEndpoint = 'https://payment.momo.vn/v2/gateway/api/query';
}

$requestId = $orderId . '_query_' . time();

// Build signature for query
$rawSignature = "accessKey={$accessKey}&orderId={$orderId}&partnerCode={$partnerCode}&requestId={$requestId}";
$signature = hash_hmac('sha256', $rawSignature, $secretKey);

$queryBody = [
    'partnerCode' => $partnerCode,
    'requestId'   => $requestId,
    'orderId'     => $orderId,
    'signature'   => $signature,
    'lang'        => 'vi',
];

$ch = curl_init($queryEndpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($queryBody),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
curl_close($ch);

$momoResult = json_decode($response, true);

// resultCode 0 = success
if ($momoResult && isset($momoResult['resultCode']) && $momoResult['resultCode'] == 0) {
    $transId = $momoResult['transId'] ?? '';

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

// Still pending or query failed
echo json_encode([
    'status' => 'pending',
    'transaction_id' => null,
]);
