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
            <span class="balance-display">Số dư: <?= number_format($balance, 0, ',', '.') ?> VNĐ</span>
            <a href="/logout" class="btn btn-danger">Đăng xuất</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <?php if (!empty($rentals)): ?>
            <div class="active-rental">
                <h2>Gói thuê của bạn (<?= count($rentals) ?>)</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên gói</th>
                                <th>Thời hạn</th>
                                <th>Giá</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rental['package_name']) ?></td>
                                    <td><?= $rental['duration_months'] ?> tháng</td>
                                    <td><?= number_format($rental['price'], 0, ',', '.') ?> VNĐ</td>
                                    <td><?= htmlspecialchars($rental['start_date']) ?></td>
                                    <td><?= htmlspecialchars($rental['end_date']) ?></td>
                                    <td>
                                        <?php 
                                        $endDate = strtotime($rental['end_date']);
                                        $now = time();
                                        if ($rental['status'] == 'active' && $endDate > $now): ?>
                                            <span class="status-active">Đang hoạt động</span>
                                        <?php else: ?>
                                            <span class="status-expired">Đã hết hạn</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                            <?php if ($balance >= $package['price']): ?>
                                <button type="button" class="btn btn-primary btn-block" onclick="showRentalModal(<?= $package['id'] ?>, '<?= htmlspecialchars($package['name']) ?>', <?= number_format($package['price'], 0, ',', '.') ?>)">Thuê ngay</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary btn-block" disabled>Không đủ tiền</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Rental Confirmation Modal -->
    <div id="rentalModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Xác nhận thuê VPS</h3>
                <button class="modal-close" onclick="hideRentalModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc muốn thuê gói <strong id="rentalPackageName"></strong> không?</p>
                <p>Giá: <strong id="rentalPackagePrice"></strong> VNĐ</p>
                <p>Số tiền sẽ được trừ từ tài khoản của bạn.</p>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="hideRentalModal()">Hủy</button>
                <button class="btn btn-primary" onclick="confirmRental()">Xác nhận thuê</button>
            </div>
        </div>
    </div>
    
    <script>
        let rentalPackageId = null;
        
        function showRentalModal(packageId, packageName, packagePrice) {
            rentalPackageId = packageId;
            document.getElementById('rentalPackageName').textContent = packageName;
            document.getElementById('rentalPackagePrice').textContent = packagePrice;
            document.getElementById('rentalModal').style.display = 'flex';
        }
        
        function hideRentalModal() {
            document.getElementById('rentalModal').style.display = 'none';
            rentalPackageId = null;
        }
        
        function confirmRental() {
            if (rentalPackageId) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '/rental';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'package_id';
                input.value = rentalPackageId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('rentalModal');
            if (event.target == modal) {
                hideRentalModal();
            }
        }
    </script>
    
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
        
        .status-expired {
            color: #dc3545;
            font-weight: bold;
        }
        
        .rentals-list {
            display: grid;
            gap: 15px;
        }
        
        .rental-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .rental-item .rental-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .rental-item .rental-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        /* Table styles */
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .data-table thead {
            background: #e74c3c;
            color: white;
        }
        
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .btn-disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #e74c3c;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 30px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-body p {
            margin: 10px 0;
            color: #333;
        }
        
        .modal-body strong {
            color: #e74c3c;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .modal-footer .btn {
            padding: 10px 20px;
            min-width: 100px;
        }
        
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .modal-footer .btn {
                width: 100%;
            }
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
        
        @media (max-width: 480px) {
            .packages-grid {
                grid-template-columns: 1fr;
            }
            
            .balance-amount {
                font-size: 28px;
            }
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
