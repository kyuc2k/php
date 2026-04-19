<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký hoạt động - VPS Treo Game Java</title>
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Nhật ký hoạt động</h1>
            <div class="user-info">
                <span>Xin chào, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?></span>
                <a href="/logout" class="btn">Đăng xuất</a>
            </div>
        </div>
        
        <div class="dashboard-content">
            <a href="/dashboard" class="btn" style="margin-bottom: 20px;">Quay lại Dashboard</a>
            
            <div class="logs-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hành động</th>
                            <th>Mô tả</th>
                            <th>IP Address</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Không có hoạt động nào</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                    <td><span class="log-action"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['description'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        .logs-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logs-table th,
        .logs-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .logs-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .logs-table tr:hover {
            background: #f9f9f9;
        }
        .log-action {
            font-weight: bold;
            color: #007bff;
        }
        
        /* Responsive table styles */
        @media (max-width: 768px) {
            .logs-container {
                padding: 15px;
                overflow-x: auto;
            }
            .logs-table {
                min-width: 600px;
            }
            .logs-table th,
            .logs-table td {
                padding: 10px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .logs-container {
                padding: 10px;
                overflow-x: auto;
            }
            .logs-table th,
            .logs-table td {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</body>
</html>
