<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VM Cloud - Điện toán đám mây</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            transition: opacity 0.3s;
        }
        .nav-links a:hover {
            opacity: 0.8;
        }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 30px;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .features {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 20px;
        }
        .features h2 {
            text-align: center;
            margin-bottom: 50px;
            font-size: 36px;
            color: #667eea;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            margin-bottom: 15px;
            color: #667eea;
        }
        .cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }
        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        .cta .btn {
            background: white;
            color: #667eea;
        }
        .footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">☁️ VM Cloud</div>
            <div class="nav-links">
                <a href="/">Trang chủ</a>
                <a href="/login">Đăng nhập</a>
                <a href="/admin">Đăng ký</a>
            </div>
        </nav>
    </header>

    <section class="hero">
        <h1>Điện toán đám mây mạnh mẽ</h1>
        <p>Tạo và quản lý máy ảo của bạn một cách dễ dàng với nền tảng VM Cloud</p>
        <a href="/login" class="btn">Bắt đầu ngay</a>
    </section>

    <section class="features">
        <h2>Tính năng nổi bật</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">🚀</div>
                <h3>Tạo VM nhanh chóng</h3>
                <p>Tạo máy ảo chỉ với vài cú click, tiết kiệm thời gian và công sức</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💻</div>
                <h3>Truy cập từ xa</h3>
                <p>Kết nối với máy ảo của bạn từ bất kỳ đâu thông qua VNC</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Hiệu suất cao</h3>
                <p>Tài nguyên được tối ưu hóa để đảm bảo hiệu suất tốt nhất</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Bảo mật</h3>
                <p>Dữ liệu của bạn được bảo vệ với các lớp bảo mật đa tầng</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Quản lý dễ dàng</h3>
                <p>Dashboard trực quan giúp bạn quản lý tất cả máy ảo</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Chi phí hợp lý</h3>
                <p>Thanh toán theo sử dụng, tối ưu chi phí cho doanh nghiệp</p>
            </div>
        </div>
    </section>

    <section class="cta">
        <h2>Sẵn sàng bắt đầu?</h2>
        <p>Đăng ký ngay để trải nghiệm dịch vụ đám mây của chúng tôi</p>
        <br>
        <a href="/login" class="btn">Đăng nhập ngay</a>
    </section>

    <footer class="footer">
        <p>&copy; 2024 VM Cloud. Tất cả quyền được bảo lưu.</p>
    </footer>
</body>
</html>
