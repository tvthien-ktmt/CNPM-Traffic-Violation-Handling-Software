<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống xử phạt giao thông</title>
    <!-- FIX: Thay Tailwind CDN để không warning -->
    <link href="https://unpkg.com/tailwindcss@^3.0.0/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo-container {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #DA251D;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            color: white;
        }
        
        .system-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .system-subtitle {
            font-size: 14px;
            color: #64748b;
        }
        
        .form-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            text-align: center;
            margin: 30px 0;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .remember-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remember-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #3b82f6;
        }
        
        .remember-label {
            font-size: 14px;
            color: #475569;
        }
        
        .forgot-link {
            font-size: 14px;
            color: #3b82f6;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .login-button {
            width: 100%;
            padding: 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .login-button:hover {
            background: #2563eb;
        }
        
        .login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .support-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .support-title {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 16px;
        }
        
        .support-contacts {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .support-contact {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 14px;
            color: #475569;
        }
        
        .contact-icon {
            width: 18px;
            height: 18px;
        }
        
        .error-message {
            display: none;
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .error-message.show {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>

<body>
    <main class="login-container">
        <div class="login-form-container">
            <!-- CARD ĐĂNG NHẬP -->
            <div class="login-card">
                <div class="text-center mb-8">
                    <div class="logo-container">
                        <svg class="logo-icon" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="#DA251D" />
                            <polygon points="12,6 13.76,10.65 18.76,10.65 14.5,13.6 16.2,18 12,15.2 7.8,18 9.5,13.6 5.24,10.65 10.24,10.65" fill="currentColor" />
                        </svg>
                    </div>
                    <h1 class="system-title">Hệ thống xử phạt giao thông</h1>
                    <p class="system-subtitle">Bộ Công An - Cục Cảnh Sát Giao Thông</p>
                </div>

                <h2 class="form-title">Đăng nhập hệ thống</h2>

                <!-- Hiển thị lỗi từ session PHP -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="error-message show">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form id="login-form" method="POST" action="/traffic/app/controllers/AuthController.php?action=login">
                    <div class="form-group">
                        <label class="form-label">
                            Số điện thoại <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <input type="tel" id="phone" name="so_dien_thoai" class="form-input" 
                               placeholder="Nhập số điện thoại" required
                               value="<?php echo $_SESSION['remembered_phone'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Mật khẩu <span class="required" style="color: #ef4444;">*</span>
                        </label>
                        <input type="password" id="password" name="mat_khau" class="form-input" 
                               placeholder="Nhập mật khẩu" required>
                    </div>

                    <div class="form-options">
                        <div class="remember-container">
                            <input type="checkbox" class="remember-checkbox" id="remember-checkbox" 
                                   <?php echo isset($_SESSION['remembered_phone']) ? 'checked' : ''; ?>>
                            <span class="remember-label">Nhớ đăng nhập</span>
                        </div>
                        <a href="#" class="forgot-link" id="forgot-link">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="login-button" id="login-btn">
                        <span id="login-button-text">Đăng nhập</span>
                    </button>
                </form>

                <div class="support-section">
                    <p class="support-title">Cần hỗ trợ? Liên hệ:</p>
                    <div class="support-contacts">
                        <span class="support-contact">
                            <svg class="contact-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            1900-1234
                        </span>
                        <span class="support-contact">
                            <svg class="contact-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            hotro@csgt.gov.vn
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- JS sẽ được load từ login.php -->
</body>
</html>