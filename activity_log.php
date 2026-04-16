<?php

session_start();
require 'config.php';
require 'auth_check.php';
require_once 'activity_logger.php';

$user = $_SESSION['user'];
$userId = $user['id'] ?? null;

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter
$filterAction = $_GET['action_filter'] ?? '';

// Count total
$countSql = "SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?";
$params = [$userId];
$types = "i";

if ($filterAction !== '') {
    $countSql .= " AND action = ?";
    $params[] = $filterAction;
    $types .= "s";
}

$stmt_count = $conn->prepare($countSql);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$totalLogs = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$totalPages = max(1, ceil($totalLogs / $perPage));

// Fetch logs
$sql = "SELECT action, details, ip_address, created_at FROM activity_logs WHERE user_id = ?";
$params2 = [$userId];
$types2 = "i";

if ($filterAction !== '') {
    $sql .= " AND action = ?";
    $params2[] = $filterAction;
    $types2 .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params2[] = $perPage;
$params2[] = $offset;
$types2 .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Action labels & icons
$actionMap = [
    'login'           => ['Đăng nhập', 'fa-sign-in-alt', '#28a745'],
    'login_google'    => ['Đăng nhập Google', 'fa-google', '#4285f4'],
    'login_failed'    => ['Đăng nhập thất bại', 'fa-times-circle', '#dc3545'],
    'signup'          => ['Đăng ký', 'fa-user-plus', '#17a2b8'],
    'signup_google'   => ['Đăng ký Google', 'fa-google', '#4285f4'],
    'change_password' => ['Đổi mật khẩu', 'fa-key', '#ffc107'],
    'forgot_password' => ['Quên mật khẩu', 'fa-envelope', '#fd7e14'],
    'reset_password'  => ['Đặt lại mật khẩu', 'fa-lock-open', '#e83e8c'],
    'upload_file'     => ['Upload file', 'fa-cloud-upload-alt', '#667eea'],
    'delete_file'     => ['Xóa file', 'fa-trash-alt', '#dc3545'],
    'upgrade_plan'    => ['Nâng cấp gói', 'fa-crown', '#f5a623'],
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê hoạt động - <?= htmlspecialchars($user['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
            font-weight: 700;
            color: #333;
        }

        .page-title i { color: #667eea; }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-back:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f8f9fa;
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-card .s-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.1rem;
            color: white;
        }

        .summary-card .s-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #333;
        }

        .summary-card .s-label {
            font-size: 0.8rem;
            color: #888;
            margin-top: 4px;
            font-weight: 500;
        }

        /* Filter */
        .filter-bar {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-bar label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        .filter-bar select {
            padding: 8px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .filter-bar select:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Log Table */
        .log-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow-x: auto;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .log-table th {
            text-align: left;
            padding: 12px 15px;
            color: #888;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #f0f0f0;
        }

        .log-table td {
            padding: 14px 15px;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.92rem;
            vertical-align: middle;
        }

        .log-table tbody tr {
            transition: background 0.2s;
        }

        .log-table tbody tr:hover {
            background: #f8f9ff;
        }

        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .action-badge i {
            font-size: 0.78rem;
        }

        .detail-text {
            color: #555;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ip-text {
            color: #999;
            font-size: 0.85rem;
            font-family: 'Courier New', monospace;
        }

        .time-text {
            color: #666;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .pagination a {
            background: rgba(255,255,255,0.95);
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }

        .pagination .current {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }

        .pagination .disabled {
            background: rgba(255,255,255,0.6);
            color: #ccc;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .page-header { flex-direction: column; text-align: center; }
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .detail-text { max-width: 160px; }
        }

        @media (max-width: 480px) {
            .summary-grid { grid-template-columns: 1fr 1fr; }
            .summary-card { padding: 15px 10px; }
            .summary-card .s-value { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar"></i>
                Thống kê hoạt động
            </h1>
            <a href="dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </div>

        <?php
        // Summary counts
        $stmt_sum = $conn->prepare("SELECT action, COUNT(*) as cnt FROM activity_logs WHERE user_id = ? GROUP BY action");
        $stmt_sum->bind_param("i", $userId);
        $stmt_sum->execute();
        $sumResult = $stmt_sum->get_result();
        $summaryCounts = [];
        while ($r = $sumResult->fetch_assoc()) {
            $summaryCounts[$r['action']] = $r['cnt'];
        }
        $stmt_sum->close();

        $summaryItems = [
            ['login',           'Đăng nhập',   '#28a745',  'fa-sign-in-alt'],
            ['upload_file',     'Upload',       '#667eea',  'fa-cloud-upload-alt'],
            ['delete_file',     'Xóa file',     '#dc3545',  'fa-trash-alt'],
            ['change_password', 'Đổi MK',       '#ffc107',  'fa-key'],
            ['upgrade_plan',    'Nâng cấp',     '#f5a623',  'fa-crown'],
        ];
        ?>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="s-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-list"></i>
                </div>
                <div class="s-value"><?= number_format($totalLogs) ?></div>
                <div class="s-label">Tổng hoạt động</div>
            </div>
            <?php foreach ($summaryItems as $si): ?>
            <div class="summary-card">
                <div class="s-icon" style="background: <?= $si[2] ?>;">
                    <i class="fas <?= $si[3] ?>"></i>
                </div>
                <div class="s-value"><?= number_format($summaryCounts[$si[0]] ?? 0) ?></div>
                <div class="s-label"><?= $si[1] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-bar">
            <label><i class="fas fa-filter"></i> Lọc:</label>
            <select onchange="location.href='activity_log.php?action_filter='+this.value">
                <option value="">Tất cả</option>
                <?php foreach ($actionMap as $key => $val): ?>
                    <option value="<?= $key ?>" <?= $filterAction === $key ? 'selected' : '' ?>><?= $val[0] ?></option>
                <?php endforeach; ?>
            </select>
            <span style="color:#999; font-size:0.85rem; margin-left:auto;">
                Trang <?= $page ?> / <?= $totalPages ?> &middot; <?= number_format($totalLogs) ?> bản ghi
            </span>
        </div>

        <div class="log-card">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <p>Chưa có hoạt động nào được ghi nhận.</p>
                </div>
            <?php else: ?>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Hành động</th>
                            <th>Chi tiết</th>
                            <th>IP</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log):
                            $info = $actionMap[$log['action']] ?? [$log['action'], 'fa-circle', '#999'];
                            $bgColor = $info[2] . '18';
                        ?>
                        <tr>
                            <td>
                                <span class="action-badge" style="background: <?= $bgColor ?>; color: <?= $info[2] ?>;">
                                    <i class="fas <?= $info[1] ?>"></i>
                                    <?= $info[0] ?>
                                </span>
                            </td>
                            <td><span class="detail-text" title="<?= htmlspecialchars($log['details']) ?>"><?= htmlspecialchars($log['details']) ?></span></td>
                            <td><span class="ip-text"><?= htmlspecialchars($log['ip_address']) ?></span></td>
                            <td><span class="time-text"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryBase = $filterAction !== '' ? "action_filter=" . urlencode($filterAction) . "&" : "";
            ?>
            <a href="?<?= $queryBase ?>page=1" class="<?= $page <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-double-left"></i></a>
            <a href="?<?= $queryBase ?>page=<?= max(1, $page - 1) ?>" class="<?= $page <= 1 ? 'disabled' : '' ?>"><i class="fas fa-angle-left"></i></a>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= $queryBase ?>page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <a href="?<?= $queryBase ?>page=<?= min($totalPages, $page + 1) ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-right"></i></a>
            <a href="?<?= $queryBase ?>page=<?= $totalPages ?>" class="<?= $page >= $totalPages ? 'disabled' : '' ?>"><i class="fas fa-angle-double-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
