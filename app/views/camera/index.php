<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giám Sát Camera - CSGT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .camera-card { transition: all 0.3s ease; border: 1px solid #e5e7eb; }
        .camera-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .webm-badge { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .video-preview { height: 200px; background: linear-gradient(135deg, #1e293b 0%, #475569 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-900 to-blue-700 text-white shadow-xl">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-4 mb-4 md:mb-0">
                    <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-video text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">Hệ Thống Giám Sát Camera</h1>
                        <p class="text-blue-200">Phát hiện và xử lý vi phạm giao thông</p>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-xl font-bold"><?php echo htmlspecialchars($officer_name ?? 'Không xác định'); ?></p>
                    <p class="text-blue-200">Cán bộ giám sát</p>
                    <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" 
                       class="inline-block mt-2 px-6 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all backdrop-blur-sm border border-white/30">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-10">
            <h2 class="text-4xl font-bold text-gray-800 mb-3">
                <i class="fas fa-video text-blue-600 mr-3"></i>Camera Giám Sát Giao Thông
            </h2>
            <p class="text-gray-600 text-lg">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                Hệ thống sử dụng định dạng WebM - phát video mượt mà trên mọi trình duyệt
            </p>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Tổng camera</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo isset($cameras) ? count($cameras) : 0; ?></p>
                    </div>
                    <i class="fas fa-video text-blue-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Đang hoạt động</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo isset($cameras) ? count($cameras) : 0; ?></p>
                    </div>
                    <i class="fas fa-wifi text-green-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Định dạng</p>
                        <p class="text-3xl font-bold text-purple-600">WebM</p>
                    </div>
                    <i class="fas fa-file-video text-purple-500 text-2xl"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Thời gian</p>
                        <p class="text-3xl font-bold text-gray-800">24/7</p>
                    </div>
                    <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Camera List -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php if (isset($cameras) && is_array($cameras)): ?>
                <?php foreach ($cameras as $camera): 
                    $video_path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/' . ($camera['video_file'] ?? '');
                    $video_exists = file_exists($video_path);
                    $video_size = $video_exists ? filesize($video_path) : 0;
                    
                    // Sử dụng toán tử null coalescing để tránh lỗi
                    $camera_id = $camera['id'] ?? 0;
                    $camera_name = $camera['name'] ?? 'Không có tên';
                    $camera_location = $camera['location'] ?? 'Không xác định';
                    $camera_description = $camera['description'] ?? ' ';
                    $camera_status = $camera['status'] ?? 'offline';
                ?>
                <div class="camera-card bg-white rounded-2xl overflow-hidden">
                    <div class="relative">
                        <div class="video-preview flex flex-col items-center justify-center">
                            <i class="fas fa-video text-white text-5xl mb-4"></i>
                            <span class="webm-badge text-white px-4 py-1 rounded-full font-bold">
                                <i class="fas fa-play-circle mr-2"></i>WEBM READY
                            </span>
                            <?php if ($video_exists): ?>
                            <div class="absolute bottom-3 left-3 bg-black/50 text-white px-3 py-1 rounded text-sm">
                                <?php echo round($video_size / 1024 / 1024, 1); ?> MB
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-4 right-4">
                            <span class="bg-green-500 text-white px-3 py-1 rounded-full font-bold">
                                <i class="fas fa-circle mr-1 animate-pulse"></i><?php echo strtoupper($camera_status); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($camera_name); ?></h3>
                        
                        <p class="text-gray-600 mb-5 flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-3 text-lg"></i>
                            <?php echo htmlspecialchars($camera_location); ?>
                        </p>
                        
                        <p class="text-gray-500 mb-6">
                            <?php echo htmlspecialchars($camera_description); ?>
                        </p>
                        
                        <div class="mb-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full <?php echo $video_exists ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                    <span class="text-sm <?php echo $video_exists ? 'text-green-700' : 'text-red-700'; ?>">
                                        <?php echo $video_exists ? ' Video vi phạm sẵn sàng' : ' Video không tồn tại'; ?>
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    <?php echo date('d/m/Y'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <a href="CameraController.php?action=view&id=<?php echo $camera_id; ?>" 
                           class="block w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 
                                  text-white text-center py-4 rounded-xl font-bold text-lg transition-all duration-300">
                            <i class="fas fa-play-circle mr-3"></i>XEM TRỰC TIẾP
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-2 text-center py-12">
                    <i class="fas fa-video-slash text-gray-400 text-6xl mb-4"></i>
                    <p class="text-gray-600 text-xl">Không có camera nào trong hệ thống</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Instructions -->
        <div class="mt-12 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-8 shadow-lg">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-graduation-cap text-blue-600 mr-3"></i>Hướng dẫn sử dụng
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-mouse-pointer text-blue-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">1. Chọn camera</h4>
                    <p class="text-gray-600">Click vào camera cần giám sát để xem video trực tiếp</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-video text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">2. Quan sát video</h4>
                    <p class="text-gray-600">Click play để xem video WebM chất lượng cao</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-pen text-red-600 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800 mb-2">3. Lập biên bản</h4>
                    <p class="text-gray-600">Nhập thông tin vi phạm và tạo biên bản xử phạt</p>
                </div>
            </div>
            
            <div class="mt-8 p-4 bg-white rounded-lg border border-blue-200">
                <p class="text-blue-800 text-center">
                    <i class="fas fa-lightbulb mr-2"></i>
                    <strong>Lưu ý:</strong> Hệ thống sử dụng định dạng WebM cho khả năng phát video tối ưu trên web
                </p>
            </div>
        </div>
    </main>

    <footer class="mt-12 py-8 bg-gradient-to-r from-gray-900 to-gray-800 text-white">
        <div class="container mx-auto px-4 text-center">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <div class="flex items-center justify-center md:justify-start mb-3">
                        <i class="fas fa-shield-alt text-blue-400 text-2xl mr-3"></i>
                        <h3 class="text-xl font-bold">Hệ thống camera CSGT</h3>
                    </div>
                    <p class="text-gray-400">Phiên bản 2.0 với hỗ trợ WebM</p>
                </div>
                
                <div class="text-center md:text-right">
                    <p class="mb-2">
                        <i class="fas fa-phone mr-2"></i>Hotline: 1900 1234
                    </p>
                    <p class="text-sm text-gray-400">
                        © 2025 Bộ Công An - Cục Cảnh Sát Giao Thông
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>