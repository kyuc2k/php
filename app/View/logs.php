<?php require_once __DIR__ . '/layout/header.php'; ?>

    <div class="page-content">
        <h1>Nhật ký hoạt động</h1>

        <div class="filter-section">
            <form method="GET" action="/logs" class="filter-form">
                <div class="filter-group">
                    <label for="action-filter">Lọc theo hành động:</label>
                    <select id="action-filter" name="action" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        <?php foreach ($actions as $act): ?>
                            <option value="<?= htmlspecialchars($act) ?>" <?= ($action === $act) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($act) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort-filter">Sắp xếp theo:</label>
                    <select id="sort-filter" name="sort" onchange="this.form.submit()">
                        <option value="created_at" <?= ($sort === 'created_at') ? 'selected' : '' ?>>Thời gian</option>
                        <option value="action" <?= ($sort === 'action') ? 'selected' : '' ?>>Hành động</option>
                        <option value="description" <?= ($sort === 'description') ? 'selected' : '' ?>>Mô tả</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="order-filter">Thứ tự:</label>
                    <select id="order-filter" name="order" onchange="this.form.submit()">
                        <option value="DESC" <?= ($order === 'DESC') ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="ASC" <?= ($order === 'ASC') ? 'selected' : '' ?>>Cũ nhất</option>
                    </select>
                </div>

                <button type="button" class="btn btn-secondary" onclick="window.location.href='/logs'">Xóa bộ lọc</button>
            </form>
        </div>

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

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="/logs?page=<?= $page - 1 ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?><?= $order ? '&order=' . urlencode($order) : '' ?>" class="pagination-link">&laquo; Trang trước</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="pagination-current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="/logs?page=<?= $i ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?><?= $order ? '&order=' . urlencode($order) : '' ?>" class="pagination-link"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="/logs?page=<?= $page + 1 ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $sort ? '&sort=' . urlencode($sort) : '' ?><?= $order ? '&order=' . urlencode($order) : '' ?>" class="pagination-link">Trang sau &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination-link,
        .pagination-current {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination-link {
            background: #fff;
            border: 1px solid #ddd;
            color: #333;
        }

        .pagination-link:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination-current {
            background: #007bff;
            color: white;
            font-weight: bold;
            border: 1px solid #007bff;
        }

        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 500;
            color: #333;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 150px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

<?php require_once __DIR__ . '/layout/footer.php'; ?>
