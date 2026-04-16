<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="YAoDQkaWb60XBqE7uJfeSOyeGp5_I_TFJQS46jNx38c" />
    <title>PDF Manager - Hệ thống quản lý file PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }
        .hero-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        .hero-background {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            opacity: 0.4;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            width: 100%;
        }
        .hero-logo { margin-bottom: 40px; animation: float 3s ease-in-out infinite; }
        .hero-logo i {
            font-size: 4rem;
            color: white;
            background: rgba(255, 255, 255, 0.1);
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            letter-spacing: -1px;
            line-height: 1;
        }
        .hero-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            font-weight: 300;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .hero-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        .feature-icon { font-size: 2.5rem; color: white; margin-bottom: 20px; }
        .feature-title { font-size: 1.2rem; font-weight: 600; color: white; margin-bottom: 10px; }
        .feature-description { color: rgba(255, 255, 255, 0.8); font-size: 0.95rem; line-height: 1.5; }
        .hero-actions { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .btn:hover::before { left: 100%; }
        .btn-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
        }
        .floating-shapes {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float-up 20s infinite linear;
        }
        .shape:nth-child(1) { width: 80px; height: 80px; left: 10%; top: 20%; animation-delay: 0s; }
        .shape:nth-child(2) { width: 60px; height: 60px; right: 15%; top: 60%; animation-delay: 5s; }
        .shape:nth-child(3) { width: 40px; height: 40px; left: 20%; bottom: 30%; animation-delay: 10s; }
        .shape:nth-child(4) { width: 100px; height: 100px; right: 10%; bottom: 20%; animation-delay: 15s; }
        @keyframes float-up {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.4; }
            90% { opacity: 0.4; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 2.5rem; }
            .hero-subtitle { font-size: 1.1rem; }
            .hero-features { grid-template-columns: 1fr; gap: 20px; }
            .feature-card { padding: 25px; }
            .hero-actions { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 300px; justify-content: center; }
        }
        @media (max-width: 480px) {
            .hero-container { padding: 15px; }
            .hero-title { font-size: 2rem; }
            .hero-logo i { font-size: 3rem; width: 90px; height: 90px; }
            .feature-icon { font-size: 2rem; }
            .btn { padding: 12px 25px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="hero-background"></div>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-logo">
                <i class="fas fa-file-pdf"></i>
            </div>
            <h1 class="hero-title">PDF Manager</h1>
            <p class="hero-subtitle">Hệ thống quản lý file PDF thông minh và bảo mật</p>
            <div class="hero-features">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="feature-title">Bảo mật cao cấp</h3>
                    <p class="feature-description">Mã hóa mật khẩu và xác thực hai lớp</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <h3 class="feature-title">Upload dễ dàng</h3>
                    <p class="feature-description">Kéo thả file và quản lý trực tuyến</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3 class="feature-title">Responsive</h3>
                    <p class="feature-description">Hoạt động trên mọi thiết bị</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h3 class="feature-title">Thống kê chi tiết</h3>
                    <p class="feature-description">Theo dõi và quản lý file hiệu quả</p>
                </div>
            </div>
            <div class="hero-actions">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                </a>
                <a href="register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Tạo tài khoản
                </a>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('mousemove', function(e) {
                const shapes = document.querySelectorAll('.shape');
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                shapes.forEach((shape, index) => {
                    const speed = (index + 1) * 0.5;
                    const xPos = (x - 0.5) * speed;
                    const yPos = (y - 0.5) * speed;
                    shape.style.transform = `translate(${xPos}px, ${yPos}px)`;
                });
            });
        });
    </script>
</body>
</html>
