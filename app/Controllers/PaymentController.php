<?php

class PaymentController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function upgrade(): void
    {
        Auth::check($this->conn);

        $user   = Auth::user();
        $userId = Auth::userId();

        $userModel   = new User($this->conn);
        $uploadModel = new Upload($this->conn);

        $currentLimit = $userModel->getStorageLimit($userId);
        $usedStorage  = $uploadModel->getUsedStorage($userId);

        $message     = $_SESSION['upgrade_message'] ?? '';
        $messageType = $_SESSION['upgrade_message_type'] ?? '';
        unset($_SESSION['upgrade_message'], $_SESSION['upgrade_message_type']);

        // Handle plan selection POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
            $plan = $_POST['plan'];
            if (in_array($plan, ['1gb', '2gb'])) {
                header("Location: payment.php?plan=" . urlencode($plan));
                exit();
            } else {
                $_SESSION['upgrade_message']      = 'Gói không hợp lệ.';
                $_SESSION['upgrade_message_type'] = 'error';
                header("Location: upgrade.php");
                exit();
            }
        }

        if ($currentLimit >= 2147483648) {
            $currentPlan = '2GB';
        } elseif ($currentLimit >= 1073741824) {
            $currentPlan = '1GB';
        } else {
            $currentPlan = number_format($currentLimit / 1024 / 1024, 0) . 'MB';
        }

        view('payment/upgrade', compact('user', 'message', 'messageType', 'currentLimit', 'usedStorage', 'currentPlan'));
    }

    public function payment(): void
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        Auth::check($this->conn);

        $user   = Auth::user();
        $userId = Auth::userId();

        $userModel    = new User($this->conn);
        $paymentModel = new Payment($this->conn);

        // VNPay Configuration
        $vnp_TmnCode    = getenv('VNP_TMN_CODE') ?: 'TCWUT67D';
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'RH8QAIMM6PEES46WSINHL7DMSU1U2RHX';
        $vnp_Url        = getenv('VNP_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $vnp_Returnurl  = getenv('VNP_RETURN_URL') ?: 'http://localhost/php/php/vnpay_return.php';
        $paymentEnv     = getenv('PAYMENT_ENV') ?: 'sandbox';

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

        $planPrices = [1073741824 => 10000, 2147483648 => 20000];
        $currentLimit = $userModel->getStorageLimit($userId);

        if ($currentLimit >= $plan['size']) {
            $_SESSION['upgrade_message'] = $currentLimit == $plan['size']
                ? 'Bạn đang sử dụng gói này rồi!' : 'Bạn đã có gói lớn hơn gói này!';
            $_SESSION['upgrade_message_type'] = 'error';
            header("Location: upgrade.php");
            exit();
        }

        // Clean old pending
        $this->conn->prepare("DELETE FROM payments WHERE user_id = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)")
            ->bind_param("i", $userId);
        $stmt_clean = $this->conn->prepare("DELETE FROM payments WHERE user_id = ? AND status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $stmt_clean->bind_param("i", $userId);
        $stmt_clean->execute();
        $stmt_clean->close();

        $orderId = 'UP' . $userId . 'T' . time() . rand(100, 999);
        $currentPlanValue = $planPrices[$currentLimit] ?? 0;
        $actualPrice = max(0, $plan['price'] - $currentPlanValue);
        $isUpgrade   = $currentPlanValue > 0;

        // Build VNPay URL
        $startTime = date('YmdHis');
        $expire    = date('YmdHis', strtotime('+15 minutes', strtotime($startTime)));

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => (int)($actualPrice * 100),
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => $startTime,
            "vnp_CurrCode"   => "VND",
            "vnp_ExpireDate" => $expire,
            "vnp_IpAddr"     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => 'Nang cap ' . $plan['short'] . ' - User ' . $userId,
            "vnp_OrderType"  => "billpayment",
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $orderId,
        ];

        ksort($inputData);
        $query    = "";
        $hashdata = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $payUrl = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        // Save pending payment
        $requestId = $orderId . '_req';
        $paymentModel->create([
            'user_id'       => $userId,
            'plan'          => $planKey,
            'amount'        => $actualPrice,
            'storage_bytes' => $plan['size'],
            'order_id'      => $orderId,
            'request_id'    => $requestId,
        ]);

        view('payment/payment', compact(
            'user', 'plan', 'planKey', 'actualPrice', 'isUpgrade',
            'currentPlanValue', 'payUrl', 'orderId', 'paymentEnv'
        ));
    }

    public function vnpayReturn(): void
    {
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';

        $vnp_TxnRef        = $_GET['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode  = $_GET['vnp_ResponseCode'] ?? '';
        $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
        $vnp_BankCode      = $_GET['vnp_BankCode'] ?? '';
        $vnp_PayDate       = $_GET['vnp_PayDate'] ?? '';
        $vnp_SecureHash    = $_GET['vnp_SecureHash'] ?? '';

        // Build checksum
        $inputData = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_" && $key != 'vnp_SecureHash' && $key != 'vnp_SecureHashType') {
                $inputData[$key] = $value;
            }
        }
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            else { $hashData .= urlencode($key) . "=" . urlencode($value); $i = 1; }
        }

        if ($vnp_SecureHash !== hash_hmac('sha512', $hashData, $vnp_HashSecret)) {
            $_SESSION['upgrade_message']      = 'Xác minh giao dịch thất bại. Vui lòng liên hệ hỗ trợ.';
            $_SESSION['upgrade_message_type'] = 'error';
            header("Location: upgrade.php");
            exit();
        }

        $paymentModel = new Payment($this->conn);
        $userModel    = new User($this->conn);

        if ($vnp_ResponseCode == '00') {
            $payment = $paymentModel->findByOrderId($vnp_TxnRef);

            if ($payment && $payment['status'] === 'pending') {
                $paymentModel->markCompleted($payment['id'], $vnp_TransactionNo);
                $userModel->updateStorageLimit($payment['user_id'], $payment['storage_bytes']);

                // Send confirmation email
                $userInfo = $userModel->findById($payment['user_id']);
                if ($userInfo) {
                    $planLabels    = ['1gb' => 'Gói Cơ bản - 1GB', '2gb' => 'Gói Nâng cao - 2GB'];
                    $storageLabels = ['1gb' => '1GB', '2gb' => '2GB'];
                    $planLabel     = $planLabels[$payment['plan']] ?? $payment['plan'];
                    $storageLabel  = $storageLabels[$payment['plan']] ?? '';
                    $amountFmt     = number_format($payment['amount'], 0, ',', '.') . ' VNĐ';
                    $payDateFmt    = date('d/m/Y H:i:s', strtotime(
                        substr($vnp_PayDate, 0, 4) . '-' . substr($vnp_PayDate, 4, 2) . '-' . substr($vnp_PayDate, 6, 2) . ' ' .
                        substr($vnp_PayDate, 8, 2) . ':' . substr($vnp_PayDate, 10, 2) . ':' . substr($vnp_PayDate, 12, 2)
                    ));

                    $emailBody = $this->buildPaymentEmail($userInfo, $planLabel, $storageLabel, $amountFmt, $vnp_TxnRef, $vnp_TransactionNo, $vnp_BankCode, $payDateFmt);
                    Mailer::send($userInfo['email'], $userInfo['name'], '✅ Thanh toán thành công - ' . $planLabel, $emailBody, true);
                }
            }

            $plans     = ['1gb' => '1GB', '2gb' => '2GB'];
            $planLabel = $plans[$payment['plan'] ?? ''] ?? '';
            ActivityLogger::log($this->conn, $payment['user_id'], 'upgrade_plan', 'Nâng cấp lên gói ' . $planLabel . ' - Mã GD: ' . $vnp_TransactionNo);
            $_SESSION['upgrade_message']      = 'Thanh toán thành công! Đã nâng cấp lên gói ' . $planLabel . '. Email xác nhận đã được gửi.';
            $_SESSION['upgrade_message_type'] = 'success';
        } else {
            $_SESSION['upgrade_message']      = 'Thanh toán không thành công. Mã lỗi: ' . htmlspecialchars($vnp_ResponseCode);
            $_SESSION['upgrade_message_type'] = 'error';
        }

        header("Location: upgrade.php");
        exit();
    }

    public function vnpayIpn(): void
    {
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';

        $vnp_TxnRef        = $_GET['vnp_TxnRef'] ?? '';
        $vnp_ResponseCode  = $_GET['vnp_ResponseCode'] ?? '';
        $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
        $vnp_SecureHash    = $_GET['vnp_SecureHash'] ?? '';

        $logFile = BASE_PATH . '/vnpay_ipn.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . json_encode($_GET) . "\n", FILE_APPEND);

        $inputData = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_" && $key != 'vnp_SecureHash' && $key != 'vnp_SecureHashType') {
                $inputData[$key] = $value;
            }
        }
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            else { $hashData .= urlencode($key) . "=" . urlencode($value); $i = 1; }
        }

        if ($vnp_SecureHash !== hash_hmac('sha512', $hashData, $vnp_HashSecret)) {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "SIGNATURE MISMATCH\n", FILE_APPEND);
            echo json_encode(['RspCode' => '97', 'Message' => 'Invalid checksum']);
            exit();
        }

        $paymentModel = new Payment($this->conn);
        $userModel    = new User($this->conn);

        if ($vnp_ResponseCode == '00') {
            $payment = $paymentModel->findByOrderId($vnp_TxnRef);
            if ($payment && $payment['status'] === 'pending') {
                $paymentModel->markCompleted($payment['id'], $vnp_TransactionNo);
                $userModel->updateStorageLimit($payment['user_id'], $payment['storage_bytes']);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "SUCCESS: Payment {$payment['id']} upgraded user {$payment['user_id']} to {$payment['plan']}\n", FILE_APPEND);
            }
            echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
        } else {
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "FAILED: Order {$vnp_TxnRef} ResponseCode={$vnp_ResponseCode}\n", FILE_APPEND);
            echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
        }
    }

    public function vnpayCheck(): void
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit();
        }

        $orderId = $_GET['order_id'] ?? '';
        $userId  = $_SESSION['user']['id'] ?? 0;

        if (empty($orderId)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
            exit();
        }

        $paymentModel = new Payment($this->conn);
        $payment = $paymentModel->findByOrderIdAndUser($orderId, $userId);

        if (!$payment) {
            echo json_encode(['status' => 'not_found']);
            exit();
        }

        if ($payment['status'] === 'completed') {
            echo json_encode(['status' => $payment['status'], 'transaction_id' => $payment['transaction_id']]);
            exit();
        }

        // Query VNPay API
        $vnp_TmnCode    = getenv('VNP_TMN_CODE') ?: 'YOUR_VNPAY_TMN_CODE';
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';
        $vnp_ApiUrl     = getenv('VNP_API_URL') ?: 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';

        $inputData = [
            "vnp_RequestId"       => time() . rand(1000, 9999),
            "vnp_Version"         => "2.1.0",
            "vnp_Command"         => "querydr",
            "vnp_TmnCode"         => $vnp_TmnCode,
            "vnp_TxnRef"          => $orderId,
            "vnp_OrderInfo"       => "Query transaction status",
            "vnp_TransactionDate" => date('YmdHis'),
            "vnp_CreateDate"      => date('YmdHis'),
            "vnp_IpAddr"          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];

        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            else { $hashData .= urlencode($key) . "=" . urlencode($value); $i = 1; }
        }
        $inputData['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $ch = curl_init($vnp_ApiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($inputData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode(['status' => $payment['status'], 'transaction_id' => $payment['transaction_id']]);
            exit();
        }

        $vnpResult = json_decode($response, true);
        if ($vnpResult && ($vnpResult['vnp_ResponseCode'] ?? '') == '00') {
            $transId = $vnpResult['vnp_TransactionNo'] ?? '';
            $paymentModel->markCompletedIfPending($payment['id'], $transId);
            (new User($this->conn))->updateStorageLimit($userId, $payment['storage_bytes']);
            echo json_encode(['status' => 'completed', 'transaction_id' => $transId]);
            exit();
        }

        echo json_encode(['status' => $payment['status'], 'transaction_id' => $payment['transaction_id']]);
    }

    private function buildPaymentEmail(array $userInfo, string $planLabel, string $storageLabel, string $amount, string $txnRef, string $transNo, string $bankCode, string $payDate): string
    {
        return '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Inter,Arial,sans-serif;">
  <div style="max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:32px;text-align:center;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="padding-bottom:16px;">
        <table cellpadding="0" cellspacing="0" border="0"><tr><td align="center" valign="middle" width="72" height="72" style="width:72px;height:72px;background:#ffffff;border-radius:50%;font-size:36px;line-height:72px;">✓</td></tr></table>
      </td></tr><tr><td align="center"><h1 style="color:#fff;margin:0;font-size:1.5rem;font-weight:700;">Thanh toán thành công!</h1></td></tr></table>
    </div>
    <div style="padding:32px;">
      <p style="color:#555;margin-bottom:24px;">Xin chào <strong>' . htmlspecialchars($userInfo['name']) . '</strong>,</p>
      <p style="color:#555;margin-bottom:24px;">Đơn hàng của bạn đã được xử lý thành công.</p>
      <div style="background:#f8f9ff;border-radius:12px;padding:20px;margin-bottom:24px;">
        <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;width:45%;">Mã đơn hàng</td><td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($txnRef) . '</td></tr>
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;">Mã giao dịch VNPay</td><td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($transNo) . '</td></tr>
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;">Gói nâng cấp</td><td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($planLabel) . '</td></tr>
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;">Dung lượng</td><td style="padding:10px 0;font-weight:600;color:#333;">' . $storageLabel . '</td></tr>
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;">Số tiền</td><td style="padding:10px 0;font-weight:700;color:#e74c3c;font-size:1.05rem;">' . $amount . '</td></tr>
          <tr style="border-bottom:1px solid #e8ecf0;"><td style="padding:10px 0;color:#888;">Ngân hàng</td><td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($bankCode) . '</td></tr>
          <tr><td style="padding:10px 0;color:#888;">Thời gian</td><td style="padding:10px 0;font-weight:600;color:#333;">' . $payDate . '</td></tr>
        </table>
      </div>
      <div style="background:#e8f5e9;border-radius:10px;padding:16px;margin-bottom:24px;border-left:4px solid #4caf50;">
        <p style="margin:0;color:#2e7d32;font-size:0.9rem;">🎉 Tài khoản đã được nâng cấp lên <strong>' . $storageLabel . '</strong> ngay lập tức.</p>
      </div>
      <p style="color:#999;font-size:0.85rem;text-align:center;margin:0;">Cảm ơn bạn đã sử dụng dịch vụ!</p>
    </div>
  </div>
</body></html>';
    }
}
