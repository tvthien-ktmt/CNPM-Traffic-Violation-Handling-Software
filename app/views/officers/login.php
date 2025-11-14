<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống quản lý xử phạt giao thông</title>
    
    <!-- IMPORT CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/traffic/public/assets/css/components/officer-login.css">
</head>
<body class="h-full">
    <main class="login-container">
        <div class="login-form-container">
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/components/officer-login-card.php'; ?>
        </div>
        
        <footer class="login-footer">
            <p id="footer-text" class="text-sm mb-2 text-white">© 2025 Bộ Công An Việt Nam - Cục Cảnh Sát Giao Thông</p>
            <a href="/" id="home-link" class="footer-link text-sm">← Về trang chủ</a>
        </footer>
    </main>
    
    <script src="/traffic/public/assets/js/officer-login.js"></script>
</body>
</html>