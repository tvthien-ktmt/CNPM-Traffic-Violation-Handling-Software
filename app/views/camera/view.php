
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($camera['name']); ?> - Camera Trực tiếp</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: white; font-family: sans-serif; }
        .video-container { background: black; border-radius: 0.5rem; overflow: hidden; }
        video { width: 100%; height: auto; }
        .form-card { background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(10px); }
        .form-input { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(71, 85, 105, 0.5); }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="bg-gray-900 border-b border-gray-800">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="/traffic/app/controllers/CameraController.php?action=index" 
                       class="text-blue-400 hover:text-blue-300 p-2 rounded hover:bg-gray-800">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold"><?php echo htmlspecialchars($camera['name']); ?></h1>
                        <p class="text-gray-400 text-sm">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo htmlspecialchars($camera['location']); ?>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold"><?php echo htmlspecialchars($officer_name); ?></p>
                    <p class="text-sm text-gray-400">Cán bộ giám sát</p>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <!-- Hiển thị lỗi nếu có -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-900/30 border border-red-700 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Video Player -->
            <div class="lg:col-span-2">
                <!-- Video Container -->
                <div class="video-container mb-4">
                    <div class="bg-gray-800 p-4 flex justify-between items-center">
                        <h3 class="font-bold">
                            <i class="fas fa-video mr-2"></i>VIDEO TRỰC TIẾP
                        </h3>
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-circle animate-pulse mr-1"></i>LIVE
                        </span>
                    </div>
                    
                    <?php if ($video_exists): ?>
                    <!-- WebM Video Player -->
                    <video id="cameraVideo" controls class="w-full">
                        <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/webm">
                        Trình duyệt của bạn không hỗ trợ video WebM.
                    </video>
                    <?php else: ?>
                    <div class="h-64 flex flex-col items-center justify-center bg-gray-900">
                        <i class="fas fa-video-slash text-4xl text-gray-600 mb-2"></i>
                        <p class="text-gray-500">Video không khả dụng</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Camera Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-400">Trạng thái</p>
                        <p class="text-lg font-bold text-green-400">ONLINE</p>
                    </div>
                    <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-400">Định dạng</p>
                        <p class="text-lg font-bold text-purple-400">WebM</p>
                    </div>
                    <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-400">Độ phân giải</p>
                        <p class="text-lg font-bold">1080p HD</p>
                    </div>
                    <div class="bg-gray-800/50 p-4 rounded-lg text-center">
                        <p class="text-sm text-gray-400">FPS</p>
                        <p class="text-lg font-bold">30 FPS</p>
                    </div>
                </div>

                <!-- Violation Images -->
                <?php if (!empty($violation_images)): ?>
                <div class="bg-gray-800/50 rounded-lg p-4 mb-6">
                    <h4 class="text-lg font-bold mb-4">
                        <i class="fas fa-images text-blue-400 mr-2"></i>
                        Ảnh vi phạm đã ghi nhận
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($violation_images as $violation): ?>
                        <div class="relative">
                            <img src="/traffic/public/assets/images/<?php echo htmlspecialchars($violation['image']); ?>" 
                                 alt="Vi phạm" 
                                 class="w-full h-48 object-cover rounded-lg cursor-pointer hover:opacity-90"
                                 onclick="fillViolationForm('<?php echo $violation['license_plate']; ?>', <?php echo $violation['fine_amount']; ?>)">
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3 rounded-b-lg">
                                <p class="text-white font-bold text-sm"><?php echo $violation['license_plate']; ?></p>
                                <p class="text-xs text-gray-300"><?php echo $violation['timestamp']; ?></p>
                                <span class="inline-block mt-1 px-2 py-1 bg-red-600 text-white text-xs rounded">
                                    <?php echo $violation['violation_type']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Violation Form -->
            <div class="lg:col-span-1">
                <div class="form-card rounded-xl p-6 sticky top-6">
                    <h3 class="text-xl font-bold mb-6">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
                        Lập Biên Bản Vi Phạm
                    </h3>
                    
                    <form action="/traffic/app/controllers/CameraController.php?action=confirm" method="POST">
                        <input type="hidden" name="camera_id" value="<?php echo $camera['id']; ?>">
                        
                        <!-- Biển số xe -->
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2">Biển số xe *</label>
                            <input type="text" 
                                   name="license_plate" 
                                   id="licensePlateInput"
                                   class="form-input w-full px-4 py-3 rounded-lg"
                                   placeholder="51A-12345"
                                   required
                                   style="text-transform: uppercase">
                        </div>
                        
                        <!-- Loại xe -->
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2">Loại xe *</label>
                            <select name="vehicle_type" class="form-input w-full px-4 py-3 rounded-lg" required>
                                <option value="">Chọn loại xe...</option>
                                <option value="Xe máy">Xe máy</option>
                                <option value="Ô tô con">Ô tô con</option>
                                <option value="Xe tải">Xe tải</option>
                                <option value="Xe khách">Xe khách</option>
                            </select>
                        </div>
                        
                        <!-- Loại vi phạm -->
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2">Loại vi phạm *</label>
                            <select name="violation_type" 
                                    id="violationTypeSelect"
                                    class="form-input w-full px-4 py-3 rounded-lg" 
                                    required
                                    onchange="updateFineAmount()">
                                <option value="" data-fine="0">Chọn loại vi phạm...</option>
                                <option value="Vượt đèn đỏ" data-fine="800000">Vượt đèn đỏ - 800,000đ</option>
                                <option value="Không đội mũ bảo hiểm" data-fine="200000">Không đội MBH - 200,000đ</option>
                                <option value="Quá tốc độ" data-fine="1500000">Quá tốc độ - 1,500,000đ</option>
                                <option value="Đi sai làn đường" data-fine="300000">Đi sai làn đường - 300,000đ</option>
                                <option value="Sử dụng điện thoại" data-fine="800000">Sử dụng điện thoại - 800,000đ</option>
                            </select>
                        </div>
                        
                        <!-- Mức phạt -->
                        <div class="mb-4 p-3 bg-gray-800/50 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-gray-300">Mức phạt:</span>
                                <span class="text-xl font-bold text-red-400" id="fineAmount">0 VNĐ</span>
                            </div>
                            <input type="hidden" name="fine_amount" id="fineAmountInput" value="0">
                        </div>
                        
                        <!-- Thời gian vi phạm -->
                        <div class="mb-4">
                            <label class="block text-gray-300 mb-2">Thời gian vi phạm</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" 
                                       name="violation_date" 
                                       class="form-input px-3 py-2 rounded-lg"
                                       value="<?php echo date('Y-m-d'); ?>">
                                <input type="time" 
                                       name="violation_time" 
                                       class="form-input px-3 py-2 rounded-lg"
                                       value="<?php echo date('H:i'); ?>">
                            </div>
                        </div>
                        
                        <!-- Mô tả -->
                        <div class="mb-6">
                            <label class="block text-gray-300 mb-2">Mô tả chi tiết</label>
                            <textarea name="description" 
                                      rows="3"
                                      class="form-input w-full px-4 py-3 rounded-lg"
                                      placeholder="Mô tả hành vi vi phạm..."></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white py-3 rounded-lg font-bold">
                            <i class="fas fa-file-pen mr-2"></i>
                            Lập Biên Bản Vi Phạm
                        </button>
                    </form>
                    
                    <!-- Quick Stats -->
                    <div class="mt-6 pt-4 border-t border-gray-700">
                        <h4 class="font-bold mb-3">Thống kê camera</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-2 bg-gray-800/50 rounded">
                                <p class="text-xl font-bold text-blue-400"><?php echo $violation_count; ?></p>
                                <p class="text-xs text-gray-400">Vi phạm</p>
                            </div>
                            <div class="text-center p-2 bg-gray-800/50 rounded">
                                <p class="text-xl font-bold text-green-400"><?php echo $total_fine; ?></p>
                                <p class="text-xs text-gray-400">Tổng phạt</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script>
        // Format tiền VND
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }
        
        // Cập nhật mức phạt
        function updateFineAmount() {
            const select = document.getElementById('violationTypeSelect');
            const selectedOption = select.options[select.selectedIndex];
            const fine = parseInt(selectedOption.getAttribute('data-fine')) || 0;
            
            document.getElementById('fineAmount').textContent = formatCurrency(fine);
            document.getElementById('fineAmountInput').value = fine;
        }
        
        // Tự động điền form từ ảnh vi phạm
        function fillViolationForm(licensePlate, fineAmount) {
            // Điền biển số
            document.getElementById('licensePlateInput').value = licensePlate;
            
            // Tìm và chọn loại vi phạm tương ứng với mức phạt
            const select = document.getElementById('violationTypeSelect');
            for (let i = 0; i < select.options.length; i++) {
                if (parseInt(select.options[i].getAttribute('data-fine')) === fineAmount) {
                    select.selectedIndex = i;
                    updateFineAmount();
                    break;
                }
            }
            
            // Hiển thị thông báo
            alert(`Đã điền thông tin từ ảnh vi phạm:\nBiển số: ${licensePlate}\nMức phạt: ${formatCurrency(fineAmount)}`);
        }
        
        // Khởi tạo
        document.addEventListener('DOMContentLoaded', function() {
            updateFineAmount();
            
            // Auto-play video với xử lý lỗi autoplay
            const video = document.getElementById('cameraVideo');
            if (video) {
                video.addEventListener('click', function() {
                    if (video.paused) {
                        video.play().catch(e => {
                            console.log("Autoplay bị chặn, cần user click");
                        });
                    } else {
                        video.pause();
                    }
                });
            }
            
            // Update current time
            function updateCurrentTime() {
                const now = new Date();
                const timeElement = document.getElementById('currentTime');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('vi-VN');
                }
            }
            setInterval(updateCurrentTime, 1000);
            updateCurrentTime();
        });
        
        // Thông báo khi submit form
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const licensePlate = document.getElementById('licensePlateInput').value.trim();
                const violationType = document.getElementById('violationTypeSelect').value;
                
                if (!licensePlate) {
                    e.preventDefault();
                    alert('Vui lòng nhập biển số xe');
                    return false;
                }
                
                if (!violationType) {
                    e.preventDefault();
                    alert('Vui lòng chọn loại vi phạm');
                    return false;
                }
                
                // Hiển thị loading
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 2000);
                
                return true;
            });
        }
    </script>
</body>
</html>
