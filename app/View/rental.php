<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thuê VPS - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-header">
        <h1>Thuê VPS</h1>
        <div class="user-info">
            <a href="/dashboard" class="btn">Quay lại Dashboard</a>
            <a href="/logout" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="balance-section">
            <h2>Số dư tài khoản</h2>
            <p class="balance-amount"><?= number_format($balance, 0, ',', '.') ?> VNĐ</p>
            <a href="/deposit" class="btn btn-success">Nạp thêm tiền</a>
        </div>
        
        <?php if ($activeRental): ?>
            <div class="active-rental">
                <h2>Gói thuê hiện tại</h2>
                <div class="rental-info">
                    <h3><?= htmlspecialchars($activeRental['package_name']) ?></h3>
                    <p>Thời hạn: <?= $activeRental['duration_months'] ?> tháng</p>
                    <p>Giá: <?= number_format($activeRental['price'], 0, ',', '.') ?> VNĐ</p>
                    <p>Ngày bắt đầu: <?= htmlspecialchars($activeRental['start_date']) ?></p>
                    <p>Ngày kết thúc: <?= htmlspecialchars($activeRental['end_date']) ?></p>
                    <p>Trạng thái: <span class="status-active">Đang hoạt động</span></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'purchased'): ?>
            <div class="success">Thuê gói thành công!</div>
        <?php endif; ?>
        
        <div class="packages-section">
            <h2>Chọn gói thuê</h2>
            <div class="packages-grid">
                <?php foreach ($packages as $package): ?>
                    <div class="package-card">
                        <div class="package-header">
                            <h3><?= htmlspecialchars($package['name']) ?></h3>
                            <div class="package-price"><?= number_format($package['price'], 0, ',', '.') ?> VNĐ</div>
                        </div>
                        <div class="package-body">
                            <p class="package-duration"><?= $package['duration_months'] ?> tháng</p>
                            <p class="package-description"><?= htmlspecialchars($package['description']) ?></p>
                            <?php if ($activeRental): ?>
                                <button class="btn btn-disabled" disabled>Đang thuê</button>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-block">Thuê ngay</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <style>
        .balance-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .balance-section h2 {
            margin: 0 0 10px 0;
        }
        
        .balance-amount {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0 20px 0;
        }
        
        .active-rental {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        
        .active-rental h2 {
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .rental-info h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 20px;
        }
        
        .rental-info p {
            margin: 8px 0;
            color: #666;
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .packages-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .packages-section h2 {
            color: #e74c3c;
            margin-bottom: 30px;
        }
        
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .package-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .package-card:hover {
            border-color: #e74c3c;
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(231, 76, 60, 0.2);
        }
        
        .package-header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #ddd;
        }
        
        .package-header h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .package-price {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .package-body {
            padding: 20px;
            text-align: center;
        }
        
        .package-duration {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .package-description {
            color: #666;
            margin-bottom: 20px;
        }
        
        .btn-block {
            width: 100%;
            padding: 12px;
        }
        
        .btn-disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        @media (max-width: 768px) {
            .packages-grid {
                grid-template-columns: 1fr;
            }
            
            .balance-amount {
                font-size: 28px;
            }
        }
    </style>
</body>
</html>
