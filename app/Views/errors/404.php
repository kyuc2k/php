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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-icon {
            font-size: 6rem;
            color: #667eea;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .error-message {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        @media (max-width: 480px) {
            .error-container { padding: 40px 25px; }
            .error-code { font-size: 5rem; }
            .error-icon { font-size: 4rem; }
            .error-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><i class="fas fa-search"></i></div>
        <div class="error-code">404</div>
        <h1 class="error-title">Trang không tồn tại</h1>
        <p class="error-message">
            Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển.
        </p>
        <a href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>" class="btn btn-primary">
            <i class="fas fa-home"></i>
            <?= $isLoggedIn ? 'Về Dashboard' : 'Về trang chủ' ?>
        </a>
    </div>
</body>
</html>
