<?php
session_start();

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['officer_id'])) {
    header('Location: /traffic/app/views/officers/dashboard.php');
    exit();
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Xóa khoảng trắng
    $phone = preg_replace('/\s+/', '', $phone);
    
    if (empty($phone) || empty($password)) {
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ số điện thoại và mật khẩu';
    } else {
        // Load User model
        $userModelPath = $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/models/User.php';
        
        if (!file_exists($userModelPath)) {
            $_SESSION['error'] = 'Hệ thống đang bảo trì. Vui lòng thử lại sau.';
        } else {
            require_once $userModelPath;
            
            try {
                $userModel = new User();
                $loginResult = $userModel->checkLogin($phone, $password);
                
                if ($loginResult['success']) {
                    $officer = $loginResult['officer'];
                    
                    // Tự động fix status nếu empty
                    if ($officer['status'] === '') {
                        $userModel->autoFixStatus($officer['id']);
                    }
                    
                    // Đăng nhập thành công
                    $_SESSION['officer_id'] = $officer['id'];
                    $_SESSION['officer_code'] = $officer['ma_can_bo'] ?? '';
                    $_SESSION['officer_name'] = $officer['full_name'];
                    $_SESSION['officer_rank'] = $officer['rank'];
                    $_SESSION['officer_unit'] = $officer['unit'];
                    $_SESSION['officer_phone'] = $officer['phone'];
                    $_SESSION['officer_email'] = $officer['email'];
                    
                    // Remember me
                    if ($remember) {
                        setcookie('csgt_remembered_phone', $phone, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    header('Location: /traffic/app/views/officers/dashboard.php');
                    exit();
                    
                } else {
                    $_SESSION['error'] = $loginResult['error'];
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            }
        }
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống xử phạt giao thông</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS ĐẦY ĐỦ */
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .login-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 2rem;
            width: 100%;
            max-width: 28rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .login-btn {
            background-color: #2563eb;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            width: 100%;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-btn:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-alert {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #dc2626;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        .demo-notice {
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% {
                opacity: 0.9;
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.2);
            }
            70% {
                opacity: 1;
                box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
            }
            100% {
                opacity: 0.9;
                box-shadow: 0 0 0 0 rgba(37, 99, 235, 0);
            }
        }
        
        .header-icon {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
        }
        
        .support-icon {
            width: 2.5rem;
            height: 2.5rem;
            background-color: #dbeafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            transition: all 0.3s;
        }
        
        .support-item:hover .support-icon {
            background-color: #bfdbfe;
            transform: scale(1.1);
        }
        
        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            transition: color 0.2s;
        }
        
        .toggle-password:hover {
            color: #2563eb;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .header-icon {
                width: 3.5rem;
                height: 3.5rem;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>
</head>

<body class="login-bg">
    <div class="w-full max-w-md mx-auto px-4">
        <div class="login-card">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="header-icon">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-800 mb-2">
                    Hệ Thống Xử Phạt Giao Thông
                </h1>
                <p class="text-gray-600 text-sm">
                    Bộ Công An - Cục CSGT
                </p>
            </div>
            
            <!-- Form Title -->
            <h2 class="text-xl font-semibold text-gray-800 text-center mb-6">
                <i class="fas fa-sign-in-alt text-blue-600 mr-2"></i>
                Đăng Nhập Hệ Thống
            </h2>
            
            <!-- Display PHP session errors -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-alert">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 mr-3 text-lg"></i>
                        <div class="flex-1">
                            <p class="text-red-800 font-medium">
                                <?php 
                                echo htmlspecialchars($_SESSION['error']);
                                unset($_SESSION['error']);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Demo Account Notice -->
            <!-- <div class="demo-notice">
                <div class="flex items-start">
                    <i class="fas fa-user-shield text-blue-600 mt-0.5 mr-2"></i>
                    <div>
                        <p class="text-blue-800 text-sm font-medium mb-1">
                            <i class="fas fa-info-circle mr-1"></i>Thông tin đăng nhập
                        </p>
                        <p class="text-blue-700 text-xs">
                            Sử dụng số điện thoại đã đăng ký với hệ thống
                        </p>
                    </div>
                </div>
            </div> -->
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-5">
                <!-- Phone Number Field -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone text-blue-600 mr-1"></i>
                        Số điện thoại *
                    </label>
                    <div class="relative">
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo isset($_COOKIE['csgt_remembered_phone']) ? htmlspecialchars($_COOKIE['csgt_remembered_phone']) : ''; ?>"
                            placeholder="0xx xxx xxxx" 
                            required
                            class="form-input"
                            autocomplete="tel"
                        >
                    </div>
                </div>
                
                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock text-blue-600 mr-1"></i>
                        Mật khẩu *
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                            class="form-input pr-10"
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            id="toggle-password" 
                            class="toggle-password"
                            aria-label="Hiển thị mật khẩu"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember"
                            <?php echo isset($_COOKIE['csgt_remembered_phone']) ? 'checked' : ''; ?>
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900">
                            <i class="fas fa-bookmark mr-1"></i>Nhớ đăng nhập
                        </label>
                    </div>
                    
                    <button 
                        type="button" 
                        class="text-sm text-blue-600 hover:text-blue-800 hover:underline transition"
                        onclick="alert('Liên hệ quản trị viên để reset mật khẩu')"
                    >
                        <i class="fas fa-key mr-1"></i>Quên mật khẩu?
                    </button>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="login-btn"
                >
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Đăng nhập</span>
                </button>
            </form>
            
            <!-- Support Contacts -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-center text-gray-600 text-sm mb-4 font-medium">
                    <i class="fas fa-headset text-blue-600 mr-2"></i>Hỗ trợ trực tuyến
                </p>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="support-item text-center cursor-pointer" onclick="window.location.href='tel:19001234'">
                        <div class="support-icon">
                            <i class="fas fa-phone text-blue-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 block">1900 1234</span>
                        <span class="text-xs text-gray-500">Tổng đài hỗ trợ</span>
                    </div>
                    
                    <div class="support-item text-center cursor-pointer" onclick="window.location.href='mailto:hotro@csgt.gov.vn'">
                        <div class="support-icon">
                            <i class="fas fa-envelope text-blue-600"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 block">Email hỗ trợ</span>
                        <span class="text-xs text-gray-500">hotro@csgt.gov.vn</span>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-center text-gray-600 text-xs">
                        <i class="fas fa-info-circle mr-1"></i>
                        Nếu gặp sự cố đăng nhập, vui lòng liên hệ trực tiếp đơn vị quản lý.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="mt-6 text-center">
            <p class="text-blue-100 text-sm mb-3 font-medium">
                <i class="fas fa-copyright mr-1"></i>2025 Bộ Công An Việt Nam - Cục Cảnh Sát Giao Thông
            </p>
            
            <div class="flex justify-center space-x-4">
                <a href="/traffic/public/" class="inline-flex items-center text-white hover:text-blue-200 transition text-sm">
                    <i class="fas fa-home mr-1"></i>
                    Trang chủ
                </a>
                <a href="#" class="inline-flex items-center text-white hover:text-blue-200 transition text-sm" onclick="alert('Trang trợ giúp đang phát triển')">
                    <i class="fas fa-question-circle mr-1"></i>
                    Trợ giúp
                </a>
            </div>
        </footer>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const phoneInput = document.getElementById('phone');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('toggle-password');
            const loginForm = document.querySelector('form');
            
            // Auto focus vào input số điện thoại
            if (phoneInput) {
                setTimeout(() => {
                    phoneInput.focus();
                }, 300);
                
                // Auto format phone number: 090 123 4567
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (value.length > 0 && value.startsWith('0')) {
                        if (value.length <= 4) {
                            value = value;
                        } else if (value.length <= 7) {
                            value = value.substring(0, 4) + ' ' + value.substring(4);
                        } else {
                            value = value.substring(0, 4) + ' ' + value.substring(4, 7) + ' ' + value.substring(7, 11);
                        }
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Toggle password visibility
            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.className = 'fas fa-eye-slash';
                        this.setAttribute('title', 'Ẩn mật khẩu');
                    } else {
                        icon.className = 'fas fa-eye';
                        this.setAttribute('title', 'Hiển thị mật khẩu');
                    }
                });
            }
            
            // Form submission - loading state
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    // Format phone number before submission (remove spaces)
                    if (phoneInput) {
                        phoneInput.value = phoneInput.value.replace(/\s/g, '');
                    }
                    
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xác thực...';
                        submitBtn.disabled = true;
                        
                        // Auto restore sau 10s nếu lỗi
                        setTimeout(() => {
                            if (submitBtn.disabled) {
                                submitBtn.innerHTML = originalText;
                                submitBtn.disabled = false;
                            }
                        }, 10000);
                    }
                });
            }
            
            // Auto fill demo account on Ctrl+D
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'd') {
                    e.preventDefault();
                    if (phoneInput) phoneInput.value = '0901234567';
                    if (passwordInput) passwordInput.value = 'csgtvn123';
                    if (document.getElementById('remember')) {
                        document.getElementById('remember').checked = true;
                    }
                }
            });
            
            // Format phone on page load if has value
            if (phoneInput && phoneInput.value) {
                phoneInput.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>