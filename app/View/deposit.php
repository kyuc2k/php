<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <div class="layout-container">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <h1>Nạp tiền</h1>
            </div>
            <div class="header-right">
                <a href="/dashboard" class="btn btn-sm">Quay lại Dashboard</a>
                <a href="/logout" class="btn btn-danger btn-sm">Đăng xuất</a>
            </div>
        </header>

        <div class="main-wrapper">
            <!-- Left Sidebar -->
            <aside class="sidebar-left">
                <nav class="sidebar-nav">
                    <a href="/dashboard" class="nav-item">Dashboard</a>
                    <a href="/logs" class="nav-item">Xem nhật ký</a>
                    <a href="/change-password" class="nav-item">Đổi mật khẩu</a>
                    <a href="/upload-file" class="nav-item">Upload File</a>
                    <a href="/deposit" class="nav-item active">Nạp tiền</a>
                    <a href="/rental" class="nav-item">Thuê VPS</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
        <div class="deposit-section">
            <h2>Nạp tiền vào tài khoản</h2>
            
            <div class="balance-info">
                <p>Số dư hiện tại: <strong><?= number_format($balance, 0, ',', '.') ?> VNĐ</strong></p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success']) && $_GET['success'] == 'payment_success'): ?>
                <div class="success">Nạp tiền thành công! Số dư đã được cập nhật.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'payment_failed'): ?>
                <div class="error">Nạp tiền thất bại. Vui lòng thử lại.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_signature'): ?>
                <div class="error">Giao dịch không hợp lệ.</div>
            <?php endif; ?>
            
            <form method="post" class="deposit-form">
                <div class="form-group">
                    <label for="amount">Số tiền nạp (VNĐ):</label>
                    <input type="number" name="amount" id="amount" min="10000" max="50000000" step="1000" required placeholder="Nhập số tiền">
                    <small>Tối thiểu: 10,000 VNĐ - Tối đa: 50,000,000 VNĐ</small>
                </div>
                
                <div class="quick-amounts">
                    <button type="button" class="quick-amount-btn" onclick="setAmount(10000)">10,000</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(50000)">50,000</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(100000)">100,000</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(200000)">200,000</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(500000)">500,000</button>
                    <button type="button" class="quick-amount-btn" onclick="setAmount(1000000)">1,000,000</button>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large">Nạp tiền qua VNPay</button>
            </form>
            
            <div class="payment-info">
                <p><strong>Thông tin thanh toán:</strong></p>
                <ul>
                    <li>Thanh toán qua cổng VNPay</li>
                    <li>Hỗ trợ thẻ ATM, Visa, MasterCard, Momo, ZaloPay</li>
                    <li>Số tiền được cộng vào tài khoản ngay sau khi thanh toán thành công</li>
                </ul>
            </div>
        </div>
        
        <div class="history-section">
            <h2>Lịch sử nạp tiền</h2>
            <?php if (empty($deposits)): ?>
                <div class="info">Chưa có giao dịch nào.</div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Số tiền</th>
                                <th>Mã giao dịch</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td><?= number_format($deposit['amount'], 0, ',', '.') ?> VNĐ</td>
                                    <td><?= htmlspecialchars($deposit['vnp_txn_ref']) ?></td>
                                    <td><?= htmlspecialchars($deposit['created_at']) ?></td>
                                    <td>
                                        <?php if ($deposit['status'] == 'success'): ?>
                                            <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Thành công</span>
                                        <?php elseif ($deposit['status'] == 'failed'): ?>
                                            <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Thất bại</span>
                                        <?php else: ?>
                                            <span style="background: #ffc107; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold;">Đang xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
            </main>

            <!-- Right Sidebar -->
            <aside class="sidebar-right">
                <div class="sidebar-widget">
                    <h3>Thông tin tài khoản</h3>
                    <p>Số dư: <?= number_format($balance, 0, ',', '.') ?> VNĐ</p>
                    <p>Giao dịch: <?= count($deposits) ?></p>
                </div>
                <div class="sidebar-widget">
                    <h3>Liên kết nhanh</h3>
                    <a href="/rental" class="quick-link">Thuê VPS</a>
                    <a href="/dashboard" class="quick-link">Dashboard</a>
                </div>
            </aside>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <p>&copy; 2026 VPS Treo Game Java. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }
    </script>
    
    <style>
        .deposit-section, .history-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .deposit-section h2, .history-section h2 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .balance-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 18px;
        }
        
        .deposit-form {
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            border-color: #e74c3c;
            outline: none;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .quick-amounts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .quick-amount-btn {
            padding: 10px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .quick-amount-btn:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        
        .btn-large {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            margin-top: 10px;
        }
        
        .payment-info {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .payment-info p {
            margin: 10px 0;
            color: #333;
        }
        
        .payment-info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .payment-info li {
            margin: 5px 0;
            color: #666;
        }
        
        .deposits-list {
            display: grid;
            gap: 15px;
        }
        
        .deposit-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #ddd;
        }
        
        .deposit-item.status-success {
            border-left-color: #28a745;
        }
        
        .deposit-item.status-failed {
            border-left-color: #dc3545;
        }
        
        .deposit-item.status-pending {
            border-left-color: #ffc107;
        }
        
        .deposit-info h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 20px;
        }
        
        .deposit-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .deposit-section, .history-section {
                padding: 20px;
            }
            
            .quick-amounts {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .balance-info {
                font-size: 16px;
            }
        }
    </style>
</body>
</html>
