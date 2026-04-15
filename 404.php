<?php
http_response_code(404);
$isLoggedIn = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user'])) {
    $isLoggedIn = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Trang không tồn tại</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #fff;
            border-radius: 24px;
            padding: 60px 50px;
            text-align: center;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .error-code {
            font-size: 7rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }

        .error-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #764ba2;
        }

        .error-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 12px;
        }

        .error-desc {
            font-size: 1rem;
            color: #888;
            line-height: 1.6;
            margin-bottom: 36px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-secondary {
            background: #f0f0f5;
            color: #555;
        }

        .btn-secondary:hover {
            background: #e5e5ee;
            transform: translateY(-2px);
        }

        .divider {
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
            margin: 0 auto 28px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">404</div>
        <div class="divider"></div>
        <div class="error-icon">
            <i class="fas fa-map-signs"></i>
        </div>
        <h1 class="error-title">Trang không tồn tại</h1>
        <p class="error-desc">
            Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.<br>
            Vui lòng kiểm tra lại đường dẫn hoặc quay về trang chủ.
        </p>
        <div class="btn-group">
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
            <?php endif; ?>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </button>
        </div>
    </div>
</body>
</html>
