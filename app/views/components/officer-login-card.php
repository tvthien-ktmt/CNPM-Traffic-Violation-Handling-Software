<div class="login-card w-full max-w-md mx-4 p-8 fade-in">
    <!-- Logo và tiêu đề -->
    <div class="text-center mb-8">
        <div class="logo-badge">
            <svg class="w-12 h-12 text-yellow-400" fill="currentColor" viewbox="0 0 24 24">
                <path d="M12 2L13.09 8.26L20 9L13.09 9.74L12 16L10.91 9.74L4 9L10.91 8.26L12 2Z" />
                <circle cx="12" cy="12" r="3" fill="currentColor" />
            </svg>
        </div>
        <h1 id="system-title" class="text-2xl font-bold text-gray-900 mb-2">Hệ thống quản lý xử phạt giao thông</h1>
        <p id="system-subtitle" class="text-sm text-gray-600">Bộ Công An - Cục Cảnh Sát Giao Thông</p>
    </div>

    <!-- Form đăng nhập -->
    <div>
        <h2 id="form-title" class="text-xl font-semibold text-gray-900 mb-6 text-center">Đăng nhập hệ thống</h2>
        
        <!-- Thông báo lỗi -->
        <div id="error-message" class="error-message">
            <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewbox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-red-800">Đăng nhập thất bại</p>
                <p class="text-xs text-red-600 mt-1">Số điện thoại hoặc mật khẩu không chính xác. Vui lòng thử lại.</p>
            </div>
        </div>

        <form id="login-form">
            <!-- Số điện thoại -->
            <div class="input-group">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    <span id="phone-label">Số điện thoại</span>
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="Nhập số điện thoại" required pattern="[0-9]{10}" maxlength="10">
                    <svg class="input-icon w-5 h-5" fill="currentColor" viewbox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div class="input-group">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <span id="password-label">Mật khẩu</span>
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Nhập mật khẩu" required minlength="6">
                    <svg class="input-icon w-5 h-5" fill="currentColor" viewbox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                    </svg>
                </div>
            </div>

            <!-- Nhớ đăng nhập -->
            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="remember" class="checkbox-custom">
                    <span id="remember-label" class="ml-2 text-sm text-gray-700">Nhớ đăng nhập</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">Quên mật khẩu?</a>
            </div>

            <!-- Nút đăng nhập -->
            <button type="submit" id="login-btn" class="login-button">
                <span id="login-button-text">Đăng nhập</span>
                <div class="loading-spinner inline-block"></div>
            </button>
        </form>

        <!-- Thông tin hỗ trợ -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="text-center text-sm text-gray-600">
                <p class="mb-2">Cần hỗ trợ? Liên hệ:</p>
                <div class="flex justify-center space-x-4">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewbox="0 0 24 24">
                            <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                        </svg>
                        1900-1234
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewbox="0 0 24 24">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                        </svg>
                        hotro@csgt.gov.vn
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>