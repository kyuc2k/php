<?php

require_once __DIR__ . '/../Model/Rental.php';
require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/UserLog.php';

class RentalController {
    private $rentalModel;
    private $userModel;
    private $userLog;

    public function __construct() {
        session_start();
        $this->rentalModel = new Rental();
        $this->userModel = new User();
        $this->userLog = new UserLog();
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
                $activeRental = $this->rentalModel->getActiveRental($userId);
                require __DIR__ . '/../View/rental.php';
                return;
            }

            // Check if user has enough balance
            $balance = $this->userModel->getBalance($userId);
            if ($balance < $package['price']) {
                $error = 'Số dư không đủ. Vui lòng nạp thêm tiền.';
                $packages = $this->rentalModel->getAllPackages();
                $balance = $this->userModel->getBalance($userId);
                $activeRental = $this->rentalModel->getActiveRental($userId);
                require __DIR__ . '/../View/rental.php';
                return;
            }

            // Deduct balance
            $this->userModel->addBalance($userId, -$package['price']);

            // Create rental
            $result = $this->rentalModel->createRental($userId, $packageId);

            if ($result) {
                $this->userLog->create($userId, 'RENTAL_PURCHASE', 'Thuê gói: ' . $package['name'] . ' - ' . number_format($package['price']) . ' VNĐ');
                header('Location: /rental?success=purchased');
                exit;
            } else {
                // Refund if rental creation failed
                $this->userModel->addBalance($userId, $package['price']);
                $error = 'Tạo thuê thất bại';
            }
        }

        $packages = $this->rentalModel->getAllPackages();
        $balance = $this->userModel->getBalance($_SESSION['user']['id']);
        $activeRental = $this->rentalModel->getActiveRental($_SESSION['user']['id']);
        require __DIR__ . '/../View/rental.php';
    }
}
