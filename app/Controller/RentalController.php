<?php

require_once __DIR__ . '/../Model/Rental.php';
require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/UserLog.php';
require_once __DIR__ . '/../Model/Instance.php';

class RentalController {
    private $rentalModel;
    private $userModel;
    private $userLog;
    private $instanceModel;

    public function __construct() {
        session_start();
        $this->rentalModel = new Rental();
        $this->userModel = new User();
        $this->userLog = new UserLog();
        $this->instanceModel = new Instance();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'])) {
            $packageId = intval($_POST['package_id']);
            $userId = $_SESSION['user']['id'];

            // Get package details
            $package = $this->rentalModel->getPackageById($packageId);
            if (!$package) {
                $error = 'Gói thuê không tồn tại';
                $packages = $this->rentalModel->getAllPackages();
                $balance = $this->userModel->getBalance($userId);
                $rentals = $this->rentalModel->getByUserId($userId);
                require __DIR__ . '/../View/rental.php';
                return;
            }

            // Check if user has enough balance
            $balance = $this->userModel->getBalance($userId);
            if ($balance < $package['price']) {
                $error = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
                $packages = $this->rentalModel->getAllPackages();
                $balance = $this->userModel->getBalance($userId);
                $rentals = $this->rentalModel->getByUserId($userId);
                require __DIR__ . '/../View/rental.php';
                return;
            }

            // Deduct balance
            $this->userModel->addBalance($userId, -$package['price']);

            // Create rental
            $rentalId = $this->rentalModel->createRental($userId, $packageId);

            if ($rentalId) {
                
                // Generate VPS access info
                $vpsPassword = $this->generatePassword();
                $port = $this->getAvailablePort();
                $vpsHost = getenv('VPS_HOST') ?: '';
                $vpsUrl = $vpsHost . ':' . $port . '/vnc.html';
                
                // Create VPS container
                $containerName = 'vps_' . $userId . '_' . $rentalId;
                $userPath = $userId;
                $this->instanceModel->createContainer($containerName, $port, $userPath, $vpsPassword);
                
                // Update rental with VPS info
                $this->rentalModel->updateVpsInfo($rentalId, $vpsUrl, $vpsPassword);
                
                // Send email notification
                $emailSent = $this->sendRentalEmail($userId, $package, $vpsUrl, $vpsPassword);
                $emailError = '';
                if (!$emailSent) {
                    $emailError = 'Gửi email thông báo thất bại. Vui lòng kiểm tra lại cấu hình email server.';
                }
                
                $this->userLog->create($userId, 'RENTAL_PURCHASE', 'Thuê gói: ' . $package['name'] . ' - ' . number_format($package['price']) . ' VNĐ');
                $redirectUrl = '/rental?success=purchased';
                if ($emailError) {
                    $redirectUrl .= '&email_error=' . urlencode($emailError);
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                // Refund if rental creation failed
                $this->userModel->addBalance($userId, $package['price']);
                $error = 'Tạo thuê thất bại';
            }
        }

        $packages = $this->rentalModel->getAllPackages();
        $balance = $this->userModel->getBalance($_SESSION['user']['id']);
        $rentals = $this->rentalModel->getByUserId($_SESSION['user']['id']);
        require __DIR__ . '/../View/rental.php';
    }

    private function generatePassword($length = 12) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function getAvailablePort() {
        // Get a random port between 6002 and 6500
        return rand(6002, 6500);
    }

    private function sendRentalEmail($userId, $package, $vpsUrl, $vpsPassword) {
        require_once __DIR__ . '/../Model/User.php';
        $userModel = new User();
        $user = $userModel->getById($userId);
        
        if (!$user || !$user['email']) {
            return false;
        }

        require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('GMAIL_USERNAME');
            $mail->Password = getenv('GMAIL_APP_PASSWORD');
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('noreply@kyuc2k.pro', 'VPS Treo Game Java');
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Thuê VPS thành công - VPS Treo Game Java';
            
            $mail->Body = "
            <html>
            <head>
                <title>Thuê VPS thành công</title>
            </head>
            <body>
                <h2>Chúc mừng bạn đã thuê gói VPS thành công!</h2>
                <p>Thông tin thuê VPS:</p>
                <ul>
                    <li><strong>Gói thuê:</strong> {$package['name']}</li>
                    <li><strong>Thời hạn:</strong> {$package['duration_months']} tháng</li>
                    <li><strong>Giá:</strong> " . number_format($package['price'], 0, ',', '.') . " VNĐ</li>
                </ul>
                <p>Thông tin truy cập VPS:</p>
                <ul>
                    <li><strong>URL:</strong> <a href='$vpsUrl'>$vpsUrl</a></li>
                    <li><strong>Mật khẩu:</strong> $vpsPassword</li>
                </ul>
                <p>Vui lòng lưu giữ thông tin truy cập của bạn một cách an toàn.</p>
                <p>Trân trọng,<br>VPS Treo Game Java</p>
            </body>
            </html>
            ";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
