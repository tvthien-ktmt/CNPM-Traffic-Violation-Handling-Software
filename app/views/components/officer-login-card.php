<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống quản lý xử phạt giao thông</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/traffic/public/assets/css/components/login_page/officer-login-card.css">
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
                    <h1 class="system-title">Hệ thống quản lý xử phạt giao thông</h1>
                    <p class="system-subtitle">Bộ Công An - Cục Cảnh Sát Giao Thông</p>
                </div>

                <h2 class="form-title">Đăng nhập hệ thống</h2>

                <form id="login-form">
                    <div class="form-group">
                        <label class="form-label">
                            Số điện thoại <span class="required">*</span>
                        </label>
                        <input type="tel" class="form-input" placeholder="Nhập số điện thoại" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Mật khẩu <span class="required">*</span>
                        </label>
                        <input type="password" class="form-input" placeholder="Nhập mật khẩu" required>
                    </div>

                    <div class="form-options">
                        <div class="remember-container">
                            <input type="checkbox" class="remember-checkbox">
                            <span class="remember-label">Nhớ đăng nhập</span>
                        </div>
                        <a href="#" class="forgot-link">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="login-button">
                        Đăng nhập
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
</body>
</html>