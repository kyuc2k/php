<?php

require_once __DIR__ . '/../Model/Deposit.php';
require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/UserLog.php';

// Load .env file
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

class DepositController {
    private $depositModel;
    private $userModel;
    private $userLog;

    public function __construct() {
        session_start();
        $this->depositModel = new Deposit();
        $this->userModel = new User();
        $this->userLog = new UserLog();
    }

    public function deposit() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
            $amount = floatval($_POST['amount']);
            $userId = $_SESSION['user']['id'];

            // Validate amount
            if ($amount < 10000) {
                $error = 'Số tiền nạp tối thiểu là 10,000 VNĐ';
                $deposits = $this->depositModel->getByUserId($userId);
                $balance = $this->userModel->getBalance($userId);
                require __DIR__ . '/../View/deposit.php';
                return;
            }

            if ($amount > 50000000) {
                $error = 'Số tiền nạp tối đa là 50,000,000 VNĐ';
                $deposits = $this->depositModel->getByUserId($userId);
                $balance = $this->userModel->getBalance($userId);
                require __DIR__ . '/../View/deposit.php';
                return;
            }

            // Generate transaction reference
            $vnpTxnRef = date('YmdHis') . '_' . $userId . '_' . rand(1000, 9999);

            // Create deposit record
            $result = $this->depositModel->create($userId, $amount, $vnpTxnRef);

            if ($result) {
                // Redirect to VNPay
                $this->redirectToVNPay($amount, $vnpTxnRef);
            } else {
                $error = 'Tạo giao dịch thất bại';
            }
        }

        $deposits = $this->depositModel->getByUserId($_SESSION['user']['id']);
        $balance = $this->userModel->getBalance($_SESSION['user']['id']);
        require __DIR__ . '/../View/deposit.php';
    }

    private function redirectToVNPay($amount, $vnpTxnRef) {
        $vnp_TmnCode = getenv('VNP_TMN_CODE') ?: 'YOUR_TMNCODE';
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_HASHSECRET';
        $vnp_Url = getenv('VNP_URL') ?: 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $vnp_ReturnUrl = getenv('VNP_RETURN_URL') ?: 'http://' . $_SERVER['HTTP_HOST'] . '/vnpay-callback';

        $vnp_Params = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $amount * 100, // VNPay requires amount in cents
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Nap tien vao tai khoan: " . $_SESSION['user']['name'],
            "vnp_OrderType" => "250000",
            "vnp_ReturnUrl" => $vnp_ReturnUrl,
            "vnp_TxnRef" => $vnpTxnRef
        );

        ksort($vnp_Params);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($vnp_Params as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= "?" . $query . "vnp_SecureHash=" . $vnpSecureHash;

        header('Location: ' . $vnp_Url);
        exit;
    }

    public function vnpayCallback() {
        $vnp_HashSecret = getenv('VNP_HASH_SECRET') ?: 'YOUR_HASHSECRET';

        // Log for debugging
        error_log("VNPay Callback - HashSecret: " . $vnp_HashSecret);
        error_log("VNPay Callback - GET params: " . json_encode($_GET));

        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        error_log("VNPay Callback - Hashdata: " . $hashdata);
        $secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        error_log("VNPay Callback - Calculated Hash: " . $secureHash);
        error_log("VNPay Callback - Received Hash: " . $vnp_SecureHash);

        if ($secureHash == $vnp_SecureHash) {
            $vnp_ResponseCode = $_GET['vnp_ResponseCode'];
            $vnp_TxnRef = $_GET['vnp_TxnRef'];

            $deposit = $this->depositModel->getByTxnRef($vnp_TxnRef);

            if ($deposit && $deposit['status'] == 'pending') {
                if ($vnp_ResponseCode == '00') {
                    // Payment successful
                    $this->depositModel->updateStatus(
                        $deposit['id'],
                        'success',
                        $_GET['vnp_ResponseCode'],
                        $_GET['vnp_TransactionNo'],
                        $_GET['vnp_BankCode'],
                        $_GET['vnp_PayDate'],
                        $_GET['vnp_CardType']
                    );

                    // Update user balance
                    $this->userModel->addBalance($deposit['user_id'], $deposit['amount']);

                    // Log action
                    $this->userLog->create($deposit['user_id'], 'DEPOSIT_SUCCESS', 'Nạp tiền thành công: ' . number_format($deposit['amount']) . ' VNĐ');

                    header('Location: /deposit?success=payment_success');
                    exit;
                } else {
                    // Payment failed
                    $this->depositModel->updateStatus(
                        $deposit['id'],
                        'failed',
                        $_GET['vnp_ResponseCode'],
                        $_GET['vnp_TransactionNo'] ?? '',
                        $_GET['vnp_BankCode'] ?? '',
                        $_GET['vnp_PayDate'] ?? '',
                        $_GET['vnp_CardType'] ?? ''
                    );

                    $this->userLog->create($deposit['user_id'], 'DEPOSIT_FAILED', 'Nạp tiền thất bại: ' . $_GET['vnp_ResponseCode']);

                    header('Location: /deposit?error=payment_failed');
                    exit;
                }
            }
        }

        header('Location: /deposit?error=invalid_signature');
        exit;
    }
}
