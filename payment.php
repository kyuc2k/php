<?php
session_start();
require 'config.php';
require 'auth_check.php';

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Tạo bảng payments nếu chưa tồn tại
$conn->query(
    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan VARCHAR(10) NOT NULL,
        amount INT NOT NULL,
        storage_bytes BIGINT NOT NULL,
        order_id VARCHAR(100) NOT NULL UNIQUE,
        request_id VARCHAR(100) NOT NULL,
        transaction_id VARCHAR(100) DEFAULT NULL,
        status ENUM('pending','completed','failed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// MoMo API credentials
$partnerCode = getenv('MOMO_PARTNER_CODE') ?: 'MOMOBKUN20180529';
$accessKey   = getenv('MOMO_ACCESS_KEY') ?: 'klm05TvNBzhg7h7j';
$secretKey   = getenv('MOMO_SECRET_KEY') ?: 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';
$endpoint    = getenv('MOMO_ENDPOINT') ?: 'https://test-payment.momo.vn/v2/gateway/api/create';

// Validate plan
$plans = [
    '1gb' => ['size' => 1073741824, 'price' => 10000, 'label' => 'Gói Cơ bản - 1GB', 'short' => '1GB'],
    '2gb' => ['size' => 2147483648, 'price' => 15000, 'label' => 'Gói Nâng cao - 2GB', 'short' => '2GB'],
];

$planKey = $_GET['plan'] ?? '';
if (!isset($plans[$planKey])) {
    header("Location: upgrade.php");
    exit();
}

$plan = $plans[$planKey];

// Check if user already has this or higher plan
$stmt = $conn->prepare("SELECT storage_limit FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentLimit = $stmt->get_result()->fetch_assoc()['storage_limit'] ?? 5242880;
$stmt->close();

if ($currentLimit >= $plan['size']) {
    $_SESSION['upgrade_message'] = 'Bạn đã có gói bằng hoặc lớn hơn gói này!';
    $_SESSION['upgrade_message_type'] = 'error';
    header("Location: upgrade.php");
    exit();
}

// Build MoMo API request
$orderId    = $partnerCode . '_' . $userId . '_' . time();
$requestId  = $orderId . '_req';
$amount     = (string)$plan['price'];
$orderInfo  = 'Nang cap ' . $plan['short'] . ' - User ' . $userId;
$extraData  = base64_encode(json_encode(['plan' => $planKey, 'user_id' => $userId]));

// URLs - detect base URL automatically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$redirectUrl = $baseUrl . '/momo_return.php';
$ipnUrl      = $baseUrl . '/momo_ipn.php';

$requestType = 'captureWallet';

// Create HMAC SHA256 signature
$rawSignature = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
$signature = hash_hmac('sha256', $rawSignature, $secretKey);

// API request body
$requestBody = [
    'partnerCode' => $partnerCode,
    'partnerName' => 'Upload PDF Storage',
    'storeId'     => 'PDFUploadStore',
    'requestId'   => $requestId,
    'amount'      => (int)$amount,
    'orderId'     => $orderId,
    'orderInfo'   => $orderInfo,
    'redirectUrl' => $redirectUrl,
    'ipnUrl'      => $ipnUrl,
    'lang'        => 'vi',
    'extraData'   => $extraData,
    'requestType' => $requestType,
    'signature'   => $signature,
];

// Call MoMo API
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($requestBody),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

$momoError = '';
$payUrl = '';
$qrCodeUrl = '';

if ($curlError) {
    $momoError = 'Không thể kết nối đến MoMo: ' . $curlError;
} else {
    $result = json_decode($response, true);
    if (isset($result['resultCode']) && $result['resultCode'] == 0) {
        $payUrl    = $result['payUrl'] ?? '';
        $qrCodeUrl = $result['qrCodeUrl'] ?? '';

        // Save pending payment to DB
        $stmt_ins = $conn->prepare("INSERT INTO payments (user_id, plan, amount, storage_bytes, order_id, request_id, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_ins->bind_param("isiiss", $userId, $planKey, $plan['price'], $plan['size'], $orderId, $requestId);
        $stmt_ins->execute();
        $stmt_ins->close();
    } else {
        $momoError = 'MoMo trả lỗi: ' . ($result['message'] ?? 'Không xác định') . ' (Code: ' . ($result['resultCode'] ?? '?') . ')';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán MoMo - <?= htmlspecialchars($plan['label']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <!-- QR lib kept as fallback if MoMo doesn't return qrCodeUrl -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title i {
            font-size: 1.5rem;
            color: #ae2070;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }

        .btn-back {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #666;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        /* Order Summary */
        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .order-plan {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .order-plan-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .order-plan-name {
            font-weight: 700;
            font-size: 1.05rem;
            color: #333;
        }

        .order-plan-desc {
            font-size: 0.85rem;
            color: #666;
            margin-top: 2px;
        }

        .order-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ae2070;
        }

        .order-price span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Steps */
        .payment-steps {
            margin-bottom: 25px;
        }

        .payment-steps h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-steps h3 i {
            color: #ae2070;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
        }

        .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ae2070 0%, #d63384 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-text {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.5;
            padding-top: 3px;
        }

        /* QR Section */
        .qr-section {
            text-align: center;
            padding: 25px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 25px;
        }

        .qr-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ae2070;
            margin-bottom: 5px;
        }

        .qr-section .qr-subtitle {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 20px;
        }

        .qr-wrapper {
            display: inline-block;
            padding: 15px;
            background: white;
            border-radius: 15px;
            border: 3px solid #ae2070;
            box-shadow: 0 5px 20px rgba(174, 32, 112, 0.15);
            margin-bottom: 15px;
        }

        .momo-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .momo-logo-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ae2070 0%, #d63384 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            font-weight: 700;
        }

        .momo-logo span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ae2070;
        }

        /* Transfer Info */
        .transfer-info {
            background: #fff5f9;
            border: 1px solid #f8d7e8;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .transfer-info h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #ae2070;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transfer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #f0d0e0;
        }

        .transfer-row:last-child {
            border-bottom: none;
        }

        .transfer-label {
            font-size: 0.9rem;
            color: #888;
        }

        .transfer-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn {
            background: none;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 3px 8px;
            cursor: pointer;
            font-size: 0.75rem;
            color: #ae2070;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #ae2070;
            color: white;
            border-color: #ae2070;
        }

        .copy-btn.copied {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }

        /* Confirm Form */
        .confirm-section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .confirm-section h3 i {
            color: #28a745;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ae2070;
        }

        .form-group .input-hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 6px;
        }

        .confirm-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }

        .message {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .message-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }

        /* Timer */
        .timer-bar {
            text-align: center;
            padding: 12px;
            background: #fff8e1;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #856404;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .timer-bar i {
            color: #f0ad4e;
        }

        .timer-bar strong {
            color: #ae2070;
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .payment-container {
                padding: 10px;
            }

            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .payment-card {
                padding: 20px;
            }

            .order-summary {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .order-plan {
                flex-direction: column;
            }

            .transfer-row {
                flex-direction: column;
                gap: 4px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <header>
            <div class="header-title">
                <i class="fas fa-wallet"></i>
                <h1>Thanh toán MoMo</h1>
            </div>
            <a href="upgrade.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </header>

        <?php if ($momoError): ?>
            <div class="payment-card">
                <div class="message message-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($momoError) ?>
                </div>
                <div style="text-align: center; padding: 20px 0;">
                    <p style="color: #666; margin-bottom: 20px;">Không thể tạo đơn thanh toán. Vui lòng thử lại.</p>
                    <a href="payment.php?plan=<?= htmlspecialchars($planKey) ?>" class="confirm-btn" style="display: inline-flex; text-decoration: none; width: auto; padding: 14px 30px;">
                        <i class="fas fa-redo"></i>
                        Thử lại
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="payment-card">
                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="order-plan">
                        <div class="order-plan-icon">
                            <i class="fas <?= $planKey === '2gb' ? 'fa-rocket' : 'fa-database' ?>"></i>
                        </div>
                        <div>
                            <div class="order-plan-name"><?= htmlspecialchars($plan['label']) ?></div>
                            <div class="order-plan-desc">Dung lượng lưu trữ <?= $plan['short'] ?></div>
                        </div>
                    </div>
                    <div class="order-price">
                        <?= number_format($plan['price'], 0, ',', '.') ?> <span>VNĐ</span>
                    </div>
                </div>

                <!-- Timer -->
                <div class="timer-bar">
                    <i class="fas fa-clock"></i>
                    Giao dịch hết hạn sau: <strong id="countdown">15:00</strong>
                </div>

                <!-- Steps -->
                <div class="payment-steps">
                    <h3><i class="fas fa-list-ol"></i> Hướng dẫn thanh toán</h3>
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-text">Mở ứng dụng <strong>MoMo</strong> trên điện thoại</div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-text">Quét mã QR bên dưới hoặc bấm <strong>"Mở MoMo"</strong> để thanh toán</div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-text">Xác nhận thanh toán <strong><?= number_format($plan['price'], 0, ',', '.') ?> VNĐ</strong> trên MoMo</div>
                    </div>
                    <div class="step">
                        <div class="step-num">4</div>
                        <div class="step-text">Hệ thống <strong>tự động xác nhận</strong> và nâng cấp tài khoản</div>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="qr-section">
                    <div class="momo-logo">
                        <div class="momo-logo-circle">M</div>
                        <span>MoMo</span>
                    </div>
                    <h3>Quét mã để thanh toán</h3>
                    <div class="qr-subtitle">Sử dụng ứng dụng MoMo để quét</div>
                    <div class="qr-wrapper">
                        <?php if ($qrCodeUrl): ?>
                            <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="MoMo QR Code" width="220" height="220" style="display: block;">
                        <?php else: ?>
                            <div id="qrcode"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Transfer Info -->
                <div class="transfer-info">
                    <h4><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                    <div class="transfer-row">
                        <span class="transfer-label">Mã đơn hàng</span>
                        <span class="transfer-value">
                            <?= htmlspecialchars($orderId) ?>
                        </span>
                    </div>
                    <div class="transfer-row">
                        <span class="transfer-label">Gói nâng cấp</span>
                        <span class="transfer-value"><?= htmlspecialchars($plan['label']) ?></span>
                    </div>
                    <div class="transfer-row">
                        <span class="transfer-label">Số tiền</span>
                        <span class="transfer-value" style="color: #ae2070; font-size: 1.05rem;">
                            <?= number_format($plan['price'], 0, ',', '.') ?> VNĐ
                        </span>
                    </div>
                    <div class="transfer-row">
                        <span class="transfer-label">Trạng thái</span>
                        <span class="transfer-value" id="paymentStatus" style="color: #f0ad4e;">
                            <i class="fas fa-spinner fa-spin"></i> Chờ thanh toán...
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if ($payUrl): ?>
                        <a href="<?= htmlspecialchars($payUrl) ?>" class="confirm-btn" style="text-decoration: none; background: linear-gradient(135deg, #ae2070, #d63384);">
                            <i class="fas fa-external-link-alt"></i>
                            Mở MoMo để thanh toán
                        </a>
                    <?php endif; ?>
                    <div style="text-align: center; font-size: 0.85rem; color: #999;">
                        <i class="fas fa-shield-alt"></i> Giao dịch được bảo mật bởi MoMo. Hệ thống sẽ tự động xác nhận sau khi thanh toán.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if (!$momoError && !$qrCodeUrl && $payUrl): ?>
        // Fallback: generate QR from payUrl if MoMo didn't return qrCodeUrl
        var qrEl = document.getElementById("qrcode");
        if (qrEl) {
            new QRCode(qrEl, {
                text: <?= json_encode($payUrl) ?>,
                width: 220,
                height: 220,
                colorDark: "#ae2070",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }
        <?php endif; ?>

        // Countdown timer (15 minutes)
        let totalSeconds = 15 * 60;
        const countdownEl = document.getElementById('countdown');

        function updateCountdown() {
            if (!countdownEl) return;
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            countdownEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

            if (totalSeconds <= 0) {
                countdownEl.textContent = 'Hết hạn!';
                countdownEl.style.color = '#dc3545';
                return;
            }
            totalSeconds--;
            setTimeout(updateCountdown, 1000);
        }
        updateCountdown();

        <?php if (!$momoError): ?>
        // Auto-poll payment status every 5 seconds
        const orderId = <?= json_encode($orderId) ?>;
        let pollInterval = setInterval(function() {
            fetch('momo_check.php?order_id=' + encodeURIComponent(orderId))
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(pollInterval);
                        const statusEl = document.getElementById('paymentStatus');
                        if (statusEl) {
                            statusEl.innerHTML = '<i class="fas fa-check-circle"></i> Thanh toán thành công!';
                            statusEl.style.color = '#28a745';
                        }
                        setTimeout(function() {
                            window.location.href = 'upgrade.php';
                        }, 2000);
                    }
                })
                .catch(() => {});
        }, 5000);

        // Stop polling after 15 minutes
        setTimeout(function() {
            clearInterval(pollInterval);
        }, 15 * 60 * 1000);
        <?php endif; ?>
    </script>
</body>
</html>
