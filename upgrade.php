<?php
session_start();
require 'config.php';
require 'auth_check.php';

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Get current storage info
$stmt = $conn->prepare("SELECT storage_limit FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentLimit = $row['storage_limit'] ?? 5242880;
$stmt->close();

$stmt_used = $conn->prepare("SELECT COALESCE(SUM(file_size), 0) as used FROM uploads WHERE user_id = ?");
$stmt_used->bind_param("i", $userId);
$stmt_used->execute();
$usedStorage = $stmt_used->get_result()->fetch_assoc()['used'];
$stmt_used->close();

$message = $_SESSION['upgrade_message'] ?? '';
$messageType = $_SESSION['upgrade_message_type'] ?? '';
unset($_SESSION['upgrade_message'], $_SESSION['upgrade_message_type']);

// Handle upgrade - redirect to payment page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan'])) {
    $plan = $_POST['plan'];
    $validPlans = ['1gb', '2gb'];

    if (in_array($plan, $validPlans)) {
        header("Location: payment.php?plan=" . urlencode($plan));
        exit();
    } else {
        $_SESSION['upgrade_message'] = 'Gói không hợp lệ.';
        $_SESSION['upgrade_message_type'] = 'error';
        header("Location: upgrade.php");
        exit();
    }
}

// Determine current plan label
if ($currentLimit >= 2147483648) {
    $currentPlan = '2GB';
} elseif ($currentLimit >= 1073741824) {
    $currentPlan = '1GB';
} else {
    $currentPlan = number_format($currentLimit / 1024 / 1024, 0) . 'MB';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nâng cấp tài khoản</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .upgrade-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title i {
            font-size: 2rem;
            color: #f5a623;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary {
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }

        .current-plan-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .current-plan-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .current-plan-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .current-plan-text h3 {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }

        .current-plan-text .plan-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
        }

        .current-plan-usage {
            font-size: 0.95rem;
            color: #667eea;
            font-weight: 600;
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .plan-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .plan-card.popular {
            border-color: #f5a623;
        }

        .plan-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #f7b731 0%, #f5a623 100%);
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .plan-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .plan-card:first-child .plan-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .plan-card.popular .plan-icon {
            background: linear-gradient(135deg, #f7b731 0%, #f5a623 100%);
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .plan-storage {
            font-size: 1rem;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .plan-price {
            margin-bottom: 25px;
        }

        .plan-price .amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
        }

        .plan-price .currency {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        .plan-features {
            list-style: none;
            text-align: left;
            margin-bottom: 30px;
            flex: 1;
            width: 100%;
        }

        .plan-features li {
            padding: 8px 0;
            color: #555;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-features li i {
            color: #28a745;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .plan-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .plan-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .plan-btn-gold {
            background: linear-gradient(135deg, #f7b731 0%, #f5a623 100%);
            color: white;
        }

        .plan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .plan-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .plan-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Confirm Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            text-align: center;
        }

        .modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: white;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-message {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .modal-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .modal-btn-cancel {
            background: #e0e0e0;
            color: #666;
        }

        .modal-btn-cancel:hover {
            background: #d0d0d0;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }

            .plans-grid {
                grid-template-columns: 1fr;
            }

            .current-plan-card {
                flex-direction: column;
                text-align: center;
            }

            .current-plan-info {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .upgrade-container {
                padding: 10px;
            }

            .header-title h1 {
                font-size: 1.5rem;
            }

            .plan-price .amount {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="upgrade-container">
        <header>
            <div class="header-title">
                <i class="fas fa-crown"></i>
                <h1>Nâng cấp tài khoản</h1>
            </div>
            <div style="display: flex; gap: 15px;">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
                <a href="upload.php" class="btn btn-secondary">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload
                </a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="current-plan-card">
            <div class="current-plan-info">
                <div class="current-plan-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="current-plan-text">
                    <h3>Gói hiện tại</h3>
                    <div class="plan-name"><?= $currentPlan ?></div>
                </div>
            </div>
            <div class="current-plan-usage">
                Đã dùng <?= number_format($usedStorage / 1024 / 1024, 2) ?>MB / <?= $currentPlan ?>
            </div>
        </div>

        <div class="plans-grid">
            <!-- Plan 1: 1GB -->
            <div class="plan-card">
                <div class="plan-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="plan-name">Gói Cơ bản</div>
                <div class="plan-storage">1 GB lưu trữ</div>
                <div class="plan-price">
                    <span class="amount">10.000</span>
                    <span class="currency">VNĐ</span>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> 1GB dung lượng lưu trữ</li>
                    <li><i class="fas fa-check"></i> Upload file PDF không giới hạn</li>
                    <li><i class="fas fa-check"></i> Quản lý file dễ dàng</li>
                </ul>
                <?php if ($currentLimit >= 1073741824): ?>
                    <button class="plan-btn plan-btn-primary" disabled>
                        <i class="fas fa-check-circle"></i>
                        <?= $currentLimit == 1073741824 ? 'Đang sử dụng' : 'Đã vượt gói này' ?>
                    </button>
                <?php else: ?>
                    <button class="plan-btn plan-btn-primary" onclick="showConfirmModal('1gb', 'Gói Cơ bản - 1GB', '10.000 VNĐ')">
                        <i class="fas fa-arrow-up"></i>
                        Nâng cấp ngay
                    </button>
                <?php endif; ?>
            </div>

            <!-- Plan 2: 2GB -->
            <div class="plan-card popular">
                <div class="plan-badge"><i class="fas fa-star"></i> Phổ biến nhất</div>
                <div class="plan-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="plan-name">Gói Nâng cao</div>
                <div class="plan-storage">2 GB lưu trữ</div>
                <div class="plan-price">
                    <span class="amount">15.000</span>
                    <span class="currency">VNĐ</span>
                </div>
                <ul class="plan-features">
                    <li><i class="fas fa-check"></i> 2GB dung lượng lưu trữ</li>
                    <li><i class="fas fa-check"></i> Upload file PDF không giới hạn</li>
                    <li><i class="fas fa-check"></i> Quản lý file dễ dàng</li>
                    <li><i class="fas fa-check"></i> Ưu tiên hỗ trợ</li>
                </ul>
                <?php if ($currentLimit >= 2147483648): ?>
                    <button class="plan-btn plan-btn-gold" disabled>
                        <i class="fas fa-check-circle"></i>
                        Đang sử dụng
                    </button>
                <?php else: ?>
                    <button class="plan-btn plan-btn-gold" onclick="showConfirmModal('2gb', 'Gói Nâng cao - 2GB', '15.000 VNĐ')">
                        <i class="fas fa-arrow-up"></i>
                        Nâng cấp ngay
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                <i class="fas fa-crown"></i>
            </div>
            <h3 class="modal-title">Thanh toán qua MoMo</h3>
            <div class="modal-message">
                Bạn muốn nâng cấp lên <strong id="modalPlanName"></strong>?<br>
                <span style="font-size: 0.9rem; color: #999;">Bạn sẽ được chuyển sang trang thanh toán MoMo</span>
            </div>
            <div class="modal-price" id="modalPrice"></div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <form method="post" id="upgradeForm" style="display:inline;">
                    <input type="hidden" name="plan" id="upgradePlan">
                    <button type="submit" class="modal-btn modal-btn-confirm" style="background: linear-gradient(135deg, #ae2070, #d63384);">
                        <i class="fas fa-wallet"></i> Thanh toán
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showConfirmModal(plan, planName, price) {
            document.getElementById('upgradePlan').value = plan;
            document.getElementById('modalPlanName').textContent = planName;
            document.getElementById('modalPrice').textContent = price;
            document.getElementById('confirmModal').classList.add('show');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('show');
        }

        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeConfirmModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeConfirmModal();
        });
    </script>
</body>
</html>
