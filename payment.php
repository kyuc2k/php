<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start();
require 'config.php';
require 'auth_check.php';

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// VNPay Configuration
$vnp_TmnCode = getenv('VNP_TMN_CODE') ?: 'TCWUT67D';
$vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'RH8QAIMM6PEES46WSINHL7DMSU1U2RHX';
$vnp_Url = getenv('VNP_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
$vnp_Returnurl = getenv('VNP_RETURN_URL') ?: 'http://localhost/php/php/vnpay_return.php';
$paymentEnv = getenv('PAYMENT_ENV') ?: 'sandbox';

// Validate plan
$plans = [
    '1gb' => ['size' => 1073741824, 'price' => 10000, 'label' => 'Gói Cơ bản - 1GB', 'short' => '1GB'],
    '2gb' => ['size' => 2147483648, 'price' => 20000, 'label' => 'Gói Nâng cao - 2GB', 'short' => '2GB'],
];

$planKey = $_GET['plan'] ?? '';
if (!isset($plans[$planKey])) {
    header("Location: upgrade.php");
    exit();
}

$plan = $plans[$planKey];

// Plan prices map for discount calculation
$planPrices = [
    1073741824 => 10000, // 1GB
    2147483648 => 20000, // 2GB
];

// Check if user already has this or higher plan
$stmt = $conn->prepare("SELECT storage_limit FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentLimit = $stmt->get_result()->fetch_assoc()['storage_limit'] ?? 5242880;
$stmt->close();

if ($currentLimit >= $plan['size']) {
    if ($currentLimit == $plan['size']) {
        $_SESSION['upgrade_message'] = 'Bạn đang sử dụng gói này rồi!';
    } else {
        $_SESSION['upgrade_message'] = 'Bạn đã có gói lớn hơn gói này!';
    }
    $_SESSION['upgrade_message_type'] = 'error';
    header("Location: upgrade.php");
    exit();
}

// Clean up old pending payments (older than 30 minutes)
$stmt_clean = $conn->prepare("DELETE FROM payments WHERE user_id = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$stmt_clean->bind_param("i", $userId);
$stmt_clean->execute();
$stmt_clean->close();

// Generate fresh order ID (VNPay requires new orderId each time)
$orderId = 'UP' . $userId . 'T' . time() . rand(100, 999);

$vnpError = '';
$payUrl = '';

// Calculate actual price (deduct value of current plan if upgrading)
$currentPlanValue = $planPrices[$currentLimit] ?? 0;
$actualPrice = max(0, $plan['price'] - $currentPlanValue);
$isUpgrade = $currentPlanValue > 0;

// Build VNPay payment URL
$vnp_TxnRef = $orderId;
$vnp_OrderInfo = 'Nang cap ' . $plan['short'] . ' - User ' . $userId;
$vnp_OrderType = 'billpayment';
$vnp_Amount = $actualPrice * 100; // VNPay requires amount in cents (VND * 100)
$vnp_Locale = 'vn';
$vnp_BankCode = '';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$startTime = date('YmdHis');
$expire = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

$inputData = [
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => (int)$vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => $startTime,
    "vnp_CurrCode" => "VND",
    "vnp_ExpireDate" => $expire,
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $vnp_TxnRef,
];

if (!empty($vnp_BankCode)) {
    $inputData['vnp_BankCode'] = $vnp_BankCode;
}

// Sort and build hash data (per official VNPay example)
ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}

$vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
$payUrl = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnp_SecureHash;

// Save payment to DB
$requestId = $orderId . '_req';
$stmt_ins = $conn->prepare("INSERT INTO payments (user_id, plan, amount, storage_bytes, order_id, request_id, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$stmt_ins->bind_param("isiiss", $userId, $planKey, $actualPrice, $plan['size'], $orderId, $requestId);
$stmt_ins->execute();
$stmt_ins->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán VNPay - <?= htmlspecialchars($plan['label']) ?></title>
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
            color: #e74c3c;
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
            color: #e74c3c;
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
            color: #e74c3c;
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
            border: 3px solid #e74c3c;
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.15);
            margin-bottom: 15px;
        }

        .vnpay-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .vnpay-logo-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            font-weight: 700;
        }

        .vnpay-logo span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e74c3c;
        }

        /* Transfer Info */
        .transfer-info {
            background: #fef5f5;
            border: 1px solid #f8d7d7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .transfer-info h4 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #e74c3c;
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
            color: #e74c3c;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
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
            border-color: #e74c3c;
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
            color: #e74c3c;
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
                <h1>Thanh toán VNPay</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="upgrade.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
        </header>

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
                <div style="text-align: right;">
                    <?php if ($isUpgrade && $currentPlanValue > 0): ?>
                        <div style="font-size: 0.85rem; color: #999; text-decoration: line-through; margin-bottom: 2px;">
                            <?= number_format($plan['price'], 0, ',', '.') ?> VNĐ
                        </div>
                        <div style="font-size: 0.8rem; color: #27ae60; margin-bottom: 4px;">
                            <i class="fas fa-tag"></i> Trừ gói 1GB đã mua: -<?= number_format($currentPlanValue, 0, ',', '.') ?> VNĐ
                        </div>
                        <div class="order-price">
                            <?= number_format($actualPrice, 0, ',', '.') ?> <span>VNĐ</span>
                        </div>
                    <?php else: ?>
                        <div class="order-price">
                            <?= number_format($actualPrice, 0, ',', '.') ?> <span>VNĐ</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($paymentEnv === 'sandbox'): ?>
            <div style="background: #fff3cd; border: 1px solid #f0ad4e; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem; color: #856404; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle"></i>
                <span>Đang dùng <strong>VNPay Sandbox</strong> — thanh toán thử nghiệm (không mất tiền thật).</span>
            </div>

            <!-- Test Card Info -->
            <div style="background: #e8f6f3; border: 1px solid #1abc9c; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                <h4 style="font-size: 1rem; font-weight: 700; color: #16a085; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-credit-card"></i> Thông tin thẻ TEST
                </h4>
                <table style="width: 100%; font-size: 0.9rem; border-collapse: collapse;">
                    <tr style="border-bottom: 1px dashed #b2ebf2;">
                        <td style="padding: 8px 0; color: #555; width: 40%;">Ngân hàng</td>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">NCB</td>
                    </tr>
                    <tr style="border-bottom: 1px dashed #b2ebf2;">
                        <td style="padding: 8px 0; color: #555;">Số thẻ</td>
                        <td style="padding: 8px 0; font-weight: 600; color: #333; display: flex; align-items: center; gap: 10px;">
                            9704198526191432198
                            <button id="copyBtn" onclick="copyCardNumber()" style="background: #1abc9c; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; color: white; font-size: 0.75rem; display: flex; flex-direction: column; align-items: center; gap: 2px;">
                                <i class="fas fa-copy"></i>
                                <span id="copyText" style="font-size: 0.65rem;"></span>
                            </button>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px dashed #b2ebf2;">
                        <td style="padding: 8px 0; color: #555;">Tên chủ thẻ</td>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">NGUYEN VAN A</td>
                    </tr>
                    <tr style="border-bottom: 1px dashed #b2ebf2;">
                        <td style="padding: 8px 0; color: #555;">Ngày phát hành</td>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">07/15</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #555;">Mật khẩu OTP</td>
                        <td style="padding: 8px 0; font-weight: 600; color: #333;">123456</td>
                    </tr>
                </table>
                <div style="margin-top: 15px; padding: 12px; background: #fff; border-radius: 8px; border-left: 4px solid #1abc9c;">
                    <p style="margin: 0; font-size: 0.9rem; color: #555;">
                        <i class="fas fa-lightbulb" style="color: #f39c12;"></i>
                        <strong>Chọn phương thức thanh toán:</strong> Thẻ nội địa và tài khoản ngân hàng
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Steps -->
            <div class="payment-steps">
                <h3><i class="fas fa-list-ol"></i> Hướng dẫn thanh toán</h3>
                <div class="step">
                    <div class="step-num">1</div>
                    <div class="step-text">Nhấn nút <strong>"Thanh toán qua VNPay"</strong> bên dưới</div>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <div class="step-text">Chọn phương thức thanh toán (thẻ ATM, QR, ví điện tử...)</div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div class="step-text">Xác nhận thanh toán <strong><?= number_format($plan['price'], 0, ',', '.') ?> VNĐ</strong> trên VNPay</div>
                </div>
                <div class="step">
                    <div class="step-num">4</div>
                    <div class="step-text">Hệ thống <strong>tự động xác nhận</strong> và nâng cấp tài khoản</div>
                </div>
            </div>

            <!-- Order Info -->
            <div class="transfer-info">
                <h4><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                <div class="transfer-row">
                    <span class="transfer-label">Mã đơn hàng</span>
                    <span class="transfer-value"><?= htmlspecialchars($orderId) ?></span>
                </div>
                <div class="transfer-row">
                    <span class="transfer-label">Gói nâng cấp</span>
                    <span class="transfer-value"><?= htmlspecialchars($plan['label']) ?></span>
                </div>
                <div class="transfer-row">
                    <span class="transfer-label">Số tiền thanh toán</span>
                    <span class="transfer-value" style="color: #e74c3c; font-size: 1.05rem;">
                        <?= number_format($actualPrice, 0, ',', '.') ?> VNĐ
                        <?php if ($isUpgrade && $currentPlanValue > 0): ?>
                            <small style="display: block; font-size: 0.75rem; color: #27ae60; font-weight: 500;">
                                (Giá gốc <?= number_format($plan['price'], 0, ',', '.') ?> - đã trừ <?= number_format($currentPlanValue, 0, ',', '.') ?> VNĐ gói cũ)
                            </small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if ($payUrl): ?>
                    <a href="<?= htmlspecialchars($payUrl) ?>" class="confirm-btn" style="text-decoration: none; background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i class="fas fa-credit-card"></i>
                        Thanh toán qua VNPay
                    </a>
                <?php else: ?>
                    <div class="message message-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không thể tạo URL thanh toán. Vui lòng thử lại.
                    </div>
                    <a href="payment.php?plan=<?= htmlspecialchars($planKey) ?>" class="confirm-btn" style="display: inline-flex; text-decoration: none; background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i class="fas fa-redo"></i> Thử lại
                    </a>
                <?php endif; ?>
                <div style="text-align: center; font-size: 0.85rem; color: #999;">
                    <i class="fas fa-shield-alt"></i> Thanh toán an toàn qua cổng VNPay.
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyCardNumber() {
            navigator.clipboard.writeText('9704198526191432198').then(function() {
                var copyText = document.getElementById('copyText');
                copyText.textContent = 'đã được copy';
                setTimeout(function() {
                    copyText.textContent = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
