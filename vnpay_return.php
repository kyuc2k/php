<?php
/**
 * VNPay Return URL Handler
 * User được redirect về đây sau khi thanh toán trên VNPay.
 * Xác minh checksum, hiển thị kết quả.
 */

session_start();
require 'config.php';

$vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_VNPAY_HASH_SECRET';

// VNPay sends these params via GET
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_Amount = $_GET['vnp_Amount'] ?? 0;
$vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
$vnp_PayDate = $_GET['vnp_PayDate'] ?? '';
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';

// Build checksum data (all params except vnp_SecureHash and vnp_SecureHashType)
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
    $_SESSION['upgrade_message'] = 'Xác minh giao dịch thất bại. Vui lòng liên hệ hỗ trợ.';
    $_SESSION['upgrade_message_type'] = 'error';
    header("Location: upgrade.php");
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

        // Get user info for email
        $stmt_info = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt_info->bind_param("i", $payment['user_id']);
        $stmt_info->execute();
        $userInfo = $stmt_info->get_result()->fetch_assoc();
        $stmt_info->close();

        // Send confirmation email
        if ($userInfo) {
            $planLabels = ['1gb' => 'Gói Cơ bản - 1GB', '2gb' => 'Gói Nâng cao - 2GB'];
            $storageLabels = ['1gb' => '1GB', '2gb' => '2GB'];
            $planLabel = $planLabels[$payment['plan']] ?? $payment['plan'];
            $storageLabel = $storageLabels[$payment['plan']] ?? '';
            $amountFormatted = number_format($payment['amount'], 0, ',', '.') . ' VNĐ';
            $payDateFormatted = date('d/m/Y H:i:s', strtotime(
                substr($vnp_PayDate, 0, 4) . '-' . substr($vnp_PayDate, 4, 2) . '-' . substr($vnp_PayDate, 6, 2) . ' ' .
                substr($vnp_PayDate, 8, 2) . ':' . substr($vnp_PayDate, 10, 2) . ':' . substr($vnp_PayDate, 12, 2)
            ));

            try {
                require 'PHPMailer/src/PHPMailer.php';
                require 'PHPMailer/src/SMTP.php';
                require 'PHPMailer/src/Exception.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer();
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = getenv('GMAIL_USERNAME');
                $mail->Password = getenv('GMAIL_APP_PASSWORD');
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom(getenv('GMAIL_USERNAME'), 'PDF Manager');
                $mail->addAddress($userInfo['email'], $userInfo['name']);

                $mail->isHTML(true);
                $mail->Subject = '✅ Thanh toán thành công - ' . $planLabel;
                $mail->Body = '
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Inter,Arial,sans-serif;">
  <div style="max-width:560px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:32px;text-align:center;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td align="center" style="padding-bottom:16px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="center" valign="middle" width="72" height="72"
                    style="width:72px;height:72px;background:#ffffff;border-radius:50%;font-size:36px;line-height:72px;">
                  ✓
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center">
            <h1 style="color:#fff;margin:0;font-size:1.5rem;font-weight:700;">Thanh toán thành công!</h1>
          </td>
        </tr>
      </table>
    </div>
    <div style="padding:32px;">
      <p style="color:#555;margin-bottom:24px;">Xin chào <strong>' . htmlspecialchars($userInfo['name']) . '</strong>,</p>
      <p style="color:#555;margin-bottom:24px;">Đơn hàng của bạn đã được xử lý thành công. Dưới đây là thông tin chi tiết:</p>

      <div style="background:#f8f9ff;border-radius:12px;padding:20px;margin-bottom:24px;">
        <table style="width:100%;border-collapse:collapse;font-size:0.95rem;">
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;width:45%;">Mã đơn hàng</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($vnp_TxnRef) . '</td>
          </tr>
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;">Mã giao dịch VNPay</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($vnp_TransactionNo) . '</td>
          </tr>
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;">Gói nâng cấp</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($planLabel) . '</td>
          </tr>
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;">Dung lượng</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . $storageLabel . '</td>
          </tr>
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;">Số tiền thanh toán</td>
            <td style="padding:10px 0;font-weight:700;color:#e74c3c;font-size:1.05rem;">' . $amountFormatted . '</td>
          </tr>
          <tr style="border-bottom:1px solid #e8ecf0;">
            <td style="padding:10px 0;color:#888;">Ngân hàng</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . htmlspecialchars($vnp_BankCode) . '</td>
          </tr>
          <tr>
            <td style="padding:10px 0;color:#888;">Thời gian thanh toán</td>
            <td style="padding:10px 0;font-weight:600;color:#333;">' . $payDateFormatted . '</td>
          </tr>
        </table>
      </div>

      <div style="background:#e8f5e9;border-radius:10px;padding:16px;margin-bottom:24px;border-left:4px solid #4caf50;">
        <p style="margin:0;color:#2e7d32;font-size:0.9rem;">🎉 Tài khoản của bạn đã được nâng cấp lên <strong>' . $storageLabel . '</strong> dung lượng lưu trữ ngay lập tức.</p>
      </div>

      <p style="color:#999;font-size:0.85rem;text-align:center;margin:0;">Nếu bạn có thắc mắc, vui lòng liên hệ hỗ trợ.<br>Cảm ơn bạn đã sử dụng dịch vụ!</p>
    </div>
  </div>
</body>
</html>';
                $mail->AltBody = "Thanh toán thành công!\nMã đơn hàng: $vnp_TxnRef\nGói: $planLabel\nSố tiền: $amountFormatted\nThời gian: $payDateFormatted";
                $mail->send();
            } catch (\Exception $e) {
                // Email failure should not block the user flow
            }
        }
    }

    // Get plan label
    $plans = ['1gb' => '1GB', '2gb' => '2GB'];
    $planLabel = $plans[$payment['plan'] ?? ''] ?? '';

    $_SESSION['upgrade_message'] = 'Thanh toán thành công! Đã nâng cấp lên gói ' . $planLabel . '. Email xác nhận đã được gửi.';
    $_SESSION['upgrade_message_type'] = 'success';
} else {
    $_SESSION['upgrade_message'] = 'Thanh toán không thành công. Mã lỗi: ' . htmlspecialchars($vnp_ResponseCode);
    $_SESSION['upgrade_message_type'] = 'error';
}

header("Location: upgrade.php");
exit();
