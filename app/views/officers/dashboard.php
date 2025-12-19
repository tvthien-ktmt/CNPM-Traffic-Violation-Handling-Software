<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['officer_id'])) {
    header('Location: /traffic/app/views/officers/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng điều khiển - Hệ thống xử phạt GT</title>
    
    <!-- Tailwind CSS từ CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --accent: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .feature-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.3);
        }
        
        .avatar-container {
            position: relative;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--accent);
            color: white;
            border-radius: 9999px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.2);
        }
        
        .login-time {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-item i {
            width: 24px;
            color: var(--secondary);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.9; }
            50% { opacity: 1; }
            100% { opacity: 0.9; }
        }
        
        .glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="header-gradient text-white">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center space-x-3">
                    <div class="avatar-container relative">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                        <div class="badge pulse">✓</div>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Hệ Thống Xử Phạt Giao Thông</h1>
                        <p class="text-sm opacity-90">Cục Cảnh Sát Giao Thông - Bộ Công An</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="font-bold"><?php echo htmlspecialchars($_SESSION['officer_name'] ?? 'Cán bộ'); ?></p>
                        <p class="text-sm opacity-90"><?php echo htmlspecialchars($_SESSION['officer_rank'] ?? 'Nhân viên'); ?></p>
                    </div>
                    
                    <div class="relative">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['officer_name'] ?? 'CB'); ?>&background=3B82F6&color=fff&size=128&font-size=0.5&bold=true" 
                             alt="Avatar" 
                             class="w-12 h-12 rounded-full border-2 border-white shadow-lg">
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white"></div>
                    </div>
                    
                    <div class="login-time">
                        <i class="fas fa-clock mr-2"></i>
                        <span class="text-sm"><?php echo date('H:i'); ?></span>
                    </div>
                    
                    <a href="/traffic/app/controllers/AuthController.php?action=logout" 
                       class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-300 backdrop-blur-sm border border-white/30">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Chào mừng trở lại, <span class="text-blue-600"><?php echo htmlspecialchars($_SESSION['officer_name'] ?? 'Đồng chí'); ?></span>!
            </h1>
            <p class="text-gray-600">
                <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                Khu vực công tác: <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['officer_unit'] ?? 'Toàn quốc'); ?></span>
                • 
                <i class="fas fa-calendar-alt text-green-500 mr-2"></i>
                Hôm nay: <?php echo date('d/m/Y'); ?>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Officer Info Card -->
            <div class="card p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-id-card text-blue-600 mr-3"></i>
                        Thông Tin Cán Bộ
                    </h3>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i> Đang hoạt động
                    </span>
                </div>
                
                <div class="space-y-4">
                    <div class="info-item">
                        <i class="fas fa-fingerprint"></i>
                        <div class="ml-4 flex-1">
                            <p class="text-sm text-gray-500">Mã cán bộ</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['officer_code'] ?? 'CSGT-'.str_pad($_SESSION['officer_id'] ?? '000', 4, '0', STR_PAD_LEFT)); ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-building"></i>
                        <div class="ml-4 flex-1">
                            <p class="text-sm text-gray-500">Đơn vị công tác</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['officer_unit'] ?? 'Phòng CSGT'); ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div class="ml-4 flex-1">
                            <p class="text-sm text-gray-500">Số điện thoại</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['officer_phone'] ?? 'Chưa cập nhật'); ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="ml-4 flex-1">
                            <p class="text-sm text-gray-500">Email liên hệ</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['officer_email'] ?? 'Chưa cập nhật'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Cập nhật lần cuối: <?php echo date('H:i d/m/Y'); ?>
                    </p>
                </div>
            </div>
            
            <!-- Features Card -->
            <div class="card p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-tasks text-green-600 mr-3"></i>
                    Chức Năng Chính
                </h3>
                
                <div class="space-y-4">
                    <a href="/traffic/app/controllers/OfficerController.php?action=addViolation" 
                       class="feature-card group glow">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4 group-hover:bg-blue-200 transition-colors">
                                <i class="fas fa-edit text-blue-600 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors">Lập biên bản vi phạm</h4>
                                <p class="text-sm text-gray-600">Tạo biên bản xử phạt mới</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                        </div>
                    </a>
                    
                    <!-- ĐÃ THÊM: Lập biên bản thủ công -->
                    <a href="/traffic/app/controllers/OfficerController.php?action=createManualViolation" 
                       class="feature-card group">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4 group-hover:bg-orange-200 transition-colors">
                                <i class="fas fa-file-pen text-orange-600 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800 group-hover:text-orange-600 transition-colors">Lập biên bản thủ công</h4>
                                <p class="text-sm text-gray-600">Nhập trực tiếp & in ngay</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:text-orange-600 transition-colors"></i>
                        </div>
                    </a>
                    
                    <a href="/traffic/app/controllers/OfficerController.php?action=violationsList" 
                       class="feature-card group">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4 group-hover:bg-green-200 transition-colors">
                                <i class="fas fa-list text-green-600 text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800 group-hover:text-green-600 transition-colors">Danh sách biên bản</h4>
                                <p class="text-sm text-gray-600">Xem và quản lý biên bản đã lập</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 group-hover:text-green-600 transition-colors"></i>
                        </div>
                    </a>
                    
                    <div class="feature-card opacity-75 cursor-not-allowed">
                        <div class="flex items-center">
                            <!-- <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-video text-purple-600 text-xl"></i>
                            </div> -->
                            <div class="flex-1">
                                
<!-- Thay thế card "Nhận diện camera" cũ bằng -->
<a href="/traffic/app/controllers/CameraController.php?action=index" 
   class="feature-card group">
    <div class="flex items-center">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4 group-hover:bg-purple-200 transition-colors">
            <i class="fas fa-video text-purple-600 text-xl"></i>
        </div>
        <div class="flex-1">
            <h4 class="font-bold text-gray-800 group-hover:text-purple-600 transition-colors">Giám sát camera</h4>
            <p class="text-sm text-gray-600">Xem video và lập biên bản từ camera</p>
        </div>
        <i class="fas fa-arrow-right text-gray-400 group-hover:text-purple-600 transition-colors"></i>
    </div>
</a>
                            </div>
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="space-y-6">
                <!-- Statistics Card -->
                <div class="stat-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold mb-6">
                        <i class="fas fa-chart-line mr-3"></i>
                        Thống Kê Nhanh
                    </h3>
                    
                    <div class="quick-stats">
                        <div class="text-center p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                            <div class="stat-number">0</div>
                            <p class="text-sm opacity-90 mt-2">Hôm nay</p>
                        </div>
                        
                        <div class="text-center p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                            <div class="stat-number">0</div>
                            <p class="text-sm opacity-90 mt-2">Tổng số</p>
                        </div>
                        
                        <div class="text-center p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                            <div class="stat-number">0</div>
                            <p class="text-sm opacity-90 mt-2">VNĐ</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-white/20">
                        <p class="text-sm opacity-90">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Cập nhật thời gian thực
                        </p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card p-6">
                    <h4 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Thao tác nhanh
                    </h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button class="p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-print text-blue-600 mr-2"></i>
                            In báo cáo
                        </button>
                        <button class="p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-download text-green-600 mr-2"></i>
                            Xuất Excel
                        </button>
                        <button class="p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-cog text-purple-600 mr-2"></i>
                            Cài đặt
                        </button>
                        <button class="p-3 bg-red-50 hover:bg-red-100 rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-bell text-red-600 mr-2"></i>
                            Thông báo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="mt-8 card p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-history text-gray-600 mr-3"></i>
                    Hoạt động gần đây
                </h3>
                <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-redo mr-1"></i> Làm mới
                </button>
            </div>
            
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4 opacity-30"></i>
                <p>Chưa có hoạt động nào gần đây</p>
                <p class="text-sm mt-2">Bắt đầu lập biên bản để xem hoạt động tại đây</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-12 py-6 bg-gradient-to-r from-gray-900 to-gray-800 text-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center space-x-2 mb-2">
                        <i class="fas fa-shield-alt text-blue-400"></i>
                        <span class="font-bold">CSGT SYSTEM v1.0</span>
                    </div>
                    <p class="text-sm opacity-80">© 2025 Bộ Công An Việt Nam - Cục Cảnh Sát Giao Thông</p>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="text-center">
                        <p class="text-sm opacity-80">Phiên bản</p>
                        <p class="font-bold">1.0.0</p>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm opacity-80">Đăng nhập lúc</p>
                        <p class="font-bold"><?php echo date('H:i d/m/Y'); ?></p>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm opacity-80">Trạng thái</p>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="font-bold text-green-400">Online</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-700 text-center text-sm opacity-70">
                <p>
                    <i class="fas fa-lock mr-1"></i>
                    Hệ thống được bảo mật bởi Bộ Công An • 
                    <i class="fas fa-phone mr-1 ml-4"></i>
                    Hotline: 1900 1234 • 
                    <i class="fas fa-envelope mr-1 ml-4"></i>
                    Email: support@csgt.gov.vn
                </p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Update time every minute
        function updateTime() {
            const now = new Date();
            const timeElement = document.querySelector('.login-time span');
            if (timeElement) {
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                timeElement.textContent = `${hours}:${minutes}`;
            }
        }
        
        setInterval(updateTime, 60000);
        
        // Add hover effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add click effect to feature cards
            const featureCards = document.querySelectorAll('.feature-card:not(.cursor-not-allowed)');
            featureCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
            
            // Update greeting based on time
            const hour = new Date().getHours();
            let greeting = "Chào mừng trở lại";
            if (hour < 12) greeting = "Chào buổi sáng";
            else if (hour < 18) greeting = "Chào buổi chiều";
            else greeting = "Chào buổi tối";
            
            const greetingElement = document.querySelector('main h1');
            if (greetingElement) {
                greetingElement.innerHTML = `${greeting}, <span class="text-blue-600"><?php echo htmlspecialchars($_SESSION['officer_name'] ?? 'Đồng chí'); ?></span>!`;
            }
        });
    </script>
    
    <!-- ============ INCLUDE CHATBOT UI ============ -->
    <!-- ============ INCLUDE CHATBOT UI ============ -->
<?php
// Sửa đường dẫn này (dòng ~849)
$chatbotFile = dirname(__DIR__) . '/violations/chatbot_ui.php';
if (file_exists($chatbotFile)) {
    include $chatbotFile;
} else {
    // Fallback nếu không tìm thấy file
    echo "<!-- Chatbot UI file not found: $chatbotFile -->";
}
?>

    
</body>
</html>