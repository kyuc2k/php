<?php require_once __DIR__ . '/layout/header.php'; ?>

    <div class="page-content">
        <h1>Thuê VPS</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'purchased'): ?>
            <div class="success">Thuê gói thành công!, thông tin truy cập VPS đã gửi về mail của bạn</div>
            <?php if (isset($_GET['email_error'])): ?>
                <div class="warning" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin-top: 10px;">
                    ⚠️ <?= htmlspecialchars($_GET['email_error']) ?>
                </div>
            <?php endif; ?>
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
                                <button type="button" class="btn btn-primary btn-block" onclick="showRentalModal(<?= $package['id'] ?>, '<?= htmlspecialchars($package['name']) ?>', <?= $package['price'] ?>, this)">Thuê ngay</button>
                            <?php else: ?>
                                <a href="/deposit" class="btn btn-primary btn-block" style="text-align: center; display: block; text-decoration: none;">Không đủ tiền - Nạp ngay</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
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
                                <th>Hành động</th>
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
                                            <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Đang hoạt động</span>
                                        <?php else: ?>
                                            <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Đã hết hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($rental['vps_url'])): ?>
                                            <a href="<?= htmlspecialchars($rental['vps_url']) ?>" target="_blank" class="btn btn-primary btn-sm">Open VPS</a>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
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
        let clickedButton = null;

        function showRentalModal(packageId, packageName, packagePrice, button) {
            rentalPackageId = packageId;
            clickedButton = button;
            document.getElementById('rentalPackageName').textContent = packageName;
            document.getElementById('rentalPackagePrice').textContent = packagePrice.toLocaleString('vi-VN');
            document.getElementById('rentalModal').style.display = 'flex';
        }

        function hideRentalModal() {
            document.getElementById('rentalModal').style.display = 'none';
            rentalPackageId = null;
            clickedButton = null;
        }

        function confirmRental() {
            if (rentalPackageId) {
                // Disable confirm button and show loading
                const confirmBtn = document.querySelector('.modal-footer .btn-primary');
                const originalText = confirmBtn.textContent;
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner"></span> Đang xử lý...';

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
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
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

<?php require_once __DIR__ . '/layout/footer.php'; ?>
