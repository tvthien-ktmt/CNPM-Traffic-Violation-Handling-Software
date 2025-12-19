<?php
// Kiểm tra đăng nhập
// session_start();
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
    <title>Lập Biên Bản Thủ Công - Cán bộ CSGT</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .form-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(229, 231, 235, 0.8);
        }
        
        .section-title {
            color: #1e40af;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .violation-item {
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
        }
        
        .violation-item:hover {
            border-color: #3b82f6;
            background-color: #eff6ff;
            transform: translateY(-2px);
        }
        
        .violation-item.selected {
            border-color: #3b82f6;
            background-color: #dbeafe;
        }
        
        .fine-amount {
            font-size: 1.75rem;
            font-weight: bold;
            color: #dc2626;
        }
        
        .print-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        .form-input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.2);
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .form-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="header-gradient text-white no-print">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" 
                       class="text-white hover:text-gray-200">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold">Lập Biên Bản Thủ Công</h1>
                        <p class="text-sm opacity-90">Cán bộ: <?php echo htmlspecialchars($_SESSION['officer_name']); ?></p>
                    </div>
                </div>
                
                <div class="text-right">
                    <p class="font-medium"><?php echo htmlspecialchars($_SESSION['officer_unit']); ?></p>
                    <p class="text-sm opacity-90"><?php echo date('d/m/Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Thông báo lỗi -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 no-print">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto">
            <form id="manualViolationForm" method="POST" action="/traffic/app/controllers/OfficerController.php?action=processManualViolation">
                
                <!-- Section 1: Thông tin cán bộ -->
                <div class="form-section">
                    <h2 class="section-title text-xl font-bold">
                        <i class="fas fa-user-shield mr-2"></i>THÔNG TIN CÁN BỘ LẬP BIÊN BẢN
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Họ và tên</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['officer_name']); ?>" 
                                   class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Mã cán bộ</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['officer_code']); ?>" 
                                   class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Cấp bậc</label>
                            <input type="text" value="<?php echo htmlspecialchars($_SESSION['officer_rank']); ?>" 
                                   class="form-input bg-gray-50" readonly>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Thông tin người vi phạm -->
                <div class="form-section">
                    <h2 class="section-title text-xl font-bold">
                        <i class="fas fa-user mr-2"></i>THÔNG TIN NGƯỜI VI PHẠM
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Họ và tên <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="violator_name" required
                                   class="form-input" placeholder="Nhập họ tên đầy đủ">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Số CMND/CCCD <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="violator_id" required
                                   class="form-input" placeholder="Nhập số CMND/CCCD">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Ngày sinh</label>
                            <input type="date" name="violator_birthday"
                                   class="form-input" value="1990-01-01">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Quốc tịch</label>
                            <input type="text" name="violator_nationality"
                                   class="form-input" value="Việt Nam">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Số điện thoại</label>
                            <input type="tel" name="violator_phone"
                                   class="form-input" placeholder="Nhập số điện thoại">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Nơi ở hiện tại</label>
                            <input type="text" name="violator_address"
                                   class="form-input" placeholder="Nhập địa chỉ">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Thông tin phương tiện -->
                <div class="form-section">
                    <h2 class="section-title text-xl font-bold">
                        <i class="fas fa-car mr-2"></i>THÔNG TIN PHƯƠNG TIỆN
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Biển số xe <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="license_plate" required
                                   class="form-input text-uppercase" placeholder="VD: 29A1-12345">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Loại xe</label>
                            <select name="vehicle_type" class="form-input">
                                <option value="Xe máy">Xe máy</option>
                                <option value="Ô tô con">Ô tô con</option>
                                <option value="Ô tô tải">Ô tô tải</option>
                                <option value="Xe khách">Xe khách</option>
                                <option value="Xe container">Xe container</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Màu sắc</label>
                            <input type="text" name="vehicle_color"
                                   class="form-input" placeholder="VD: Đen, Trắng, Đỏ...">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">Nhãn hiệu</label>
                            <input type="text" name="vehicle_brand"
                                   class="form-input" placeholder="VD: Honda, Yamaha, Toyota...">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Thông tin vi phạm -->
                <div class="form-section">
                    <h2 class="section-title text-xl font-bold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>THÔNG TIN VI PHẠM
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Ngày vi phạm <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="violation_date" required
                                   class="form-input" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Giờ vi phạm <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="violation_time" required
                                   class="form-input" value="<?php echo date('H:i'); ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 text-sm font-medium mb-2">
                                Địa điểm vi phạm <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="violation_location" required
                                   class="form-input" placeholder="VD: Đường Láng Hạ, Ba Đình, Hà Nội">
                        </div>
                    </div>

                    <!-- Loại vi phạm -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-3">
                            Loại vi phạm <span class="text-red-500">*</span>
                        </label>
                        <div id="violation-types">
                            <?php
                            $violation_types = [
                                ['code' => 'VT001', 'name' => 'Vượt đèn đỏ', 'fine' => 1000000],
                                ['code' => 'VT002', 'name' => 'Không đội mũ bảo hiểm', 'fine' => 300000],
                                ['code' => 'VT003', 'name' => 'Vượt quá tốc độ', 'fine' => 800000],
                                ['code' => 'VT004', 'name' => 'Đi ngược chiều', 'fine' => 500000],
                                ['code' => 'VT005', 'name' => 'Dừng đỗ sai quy định', 'fine' => 400000],
                                ['code' => 'VT006', 'name' => 'Không có giấy phép lái xe', 'fine' => 1200000],
                                ['code' => 'VT007', 'name' => 'Sử dụng điện thoại khi lái xe', 'fine' => 600000],
                                ['code' => 'VT008', 'name' => 'Không xi nhan khi chuyển hướng', 'fine' => 200000],
                                ['code' => 'VT009', 'name' => 'Chở quá số người quy định', 'fine' => 400000],
                                ['code' => 'VT010', 'name' => 'Không đảm bảo an toàn kỹ thuật', 'fine' => 500000],
                            ];
                            
                            foreach ($violation_types as $type) {
                                echo '<div class="violation-item" data-code="' . $type['code'] . '" data-name="' . $type['name'] . '" data-fine="' . $type['fine'] . '">';
                                echo '<div class="flex justify-between items-center">';
                                echo '<div>';
                                echo '<div class="font-medium">' . $type['name'] . '</div>';
                                echo '<div class="text-sm text-gray-500 mt-1">Mã: ' . $type['code'] . '</div>';
                                echo '</div>';
                                echo '<div class="text-right">';
                                echo '<div class="font-bold text-red-600">' . number_format($type['fine'], 0, ',', '.') . ' VNĐ</div>';
                                echo '<div class="text-xs text-gray-500 mt-1">Nhấn để chọn</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <input type="hidden" name="violation_type[]" id="selectedViolations">
                    </div>

                    <!-- Nội dung chi tiết -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Nội dung vi phạm chi tiết
                        </label>
                        <textarea name="violation_content" rows="4"
                                  class="form-input" placeholder="Mô tả chi tiết về hành vi vi phạm..."></textarea>
                    </div>

                    <!-- Mức phạt -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Mức phạt tiền (VNĐ) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="fine_amount" required min="0"
                               class="form-input fine-amount" id="fineAmount" placeholder="Nhập số tiền phạt">
                        <div class="mt-2 text-sm text-gray-600">
                            Bằng chữ: <span id="amountInWords" class="font-medium">Không đồng</span>
                        </div>
                    </div>

                    <!-- Căn cứ pháp lý -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Căn cứ pháp lý
                        </label>
                        <select name="legal_basis" class="form-input">
                            <option value="Nghị định 100/2019/NĐ-CP">Nghị định 100/2019/NĐ-CP</option>
                            <option value="Nghị định 123/2021/NĐ-CP">Nghị định 123/2021/NĐ-CP</option>
                            <option value="Luật Giao thông đường bộ 2008">Luật Giao thông đường bộ 2008</option>
                        </select>
                    </div>

                    <!-- Ghi chú -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">
                            Ghi chú thêm
                        </label>
                        <textarea name="notes" rows="3"
                                  class="form-input" placeholder="Ghi chú thêm (nếu có)..."></textarea>
                    </div>
                </div>

                <!-- Tổng kết -->
                <div class="form-section bg-blue-50">
                    <h2 class="section-title text-xl font-bold text-blue-800">
                        <i class="fas fa-file-alt mr-2"></i>TỔNG KẾT BIÊN BẢN
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-blue-600" id="totalViolations">0</div>
                            <div class="text-sm text-gray-600 mt-2">Số lỗi vi phạm</div>
                        </div>
                        
                        <div class="text-center p-4 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-red-600" id="totalFineAmount">0 VNĐ</div>
                            <div class="text-sm text-gray-600 mt-2">Tổng tiền phạt</div>
                        </div>
                        
                        <div class="text-center p-4 bg-white rounded-lg">
                            <div class="text-lg font-bold text-gray-800">
                                <?php echo date('d/m/Y'); ?>
                            </div>
                            <div class="text-sm text-gray-600 mt-2">Ngày lập biên bản</div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-white rounded-lg border border-blue-200">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Biên bản này sẽ được in trực tiếp sau khi nhập đầy đủ thông tin. 
                            Không lưu vào cơ sở dữ liệu hệ thống.
                        </p>
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="form-section no-print">
                    <div class="flex justify-between items-center">
                        <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" 
                           class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i> Hủy bỏ
                        </a>
                        
                        <button type="button" onclick="printForm()" 
                                class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-print mr-2"></i> Xem trước
                        </button>
                        
                        <button type="submit" class="print-btn">
                            <i class="fas fa-file-pdf mr-2"></i> In Biên Bản
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Biến toàn cục
        let selectedViolations = [];
        let totalFine = 0;
        
        // Chuyển số thành chữ
        function numberToWords(num) {
            const ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
            const tens = ['', 'mười', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 
                         'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
            const scales = ['', 'nghìn', 'triệu', 'tỷ'];
            
            if (num === 0) return 'không đồng';
            
            let words = '';
            let scaleIndex = 0;
            
            while (num > 0) {
                const chunk = num % 1000;
                if (chunk !== 0) {
                    let chunkWords = '';
                    const hundred = Math.floor(chunk / 100);
                    const ten = Math.floor((chunk % 100) / 10);
                    const one = chunk % 10;
                    
                    if (hundred > 0) {
                        chunkWords += ones[hundred] + ' trăm ';
                    }
                    
                    if (ten > 0) {
                        if (ten === 1) {
                            chunkWords += 'mười ';
                        } else {
                            chunkWords += tens[ten] + ' ';
                        }
                    }
                    
                    if (one > 0) {
                        if (ten > 0 && one === 5) {
                            chunkWords += 'lăm ';
                        } else if (ten > 1 && one === 1) {
                            chunkWords += 'mốt ';
                        } else {
                            chunkWords += ones[one] + ' ';
                        }
                    }
                    
                    words = chunkWords + scales[scaleIndex] + ' ' + words;
                }
                
                num = Math.floor(num / 1000);
                scaleIndex++;
            }
            
            return words.trim() + ' đồng';
        }
        
        // Cập nhật tổng tiền
        function updateTotal() {
            document.getElementById('totalViolations').textContent = selectedViolations.length;
            document.getElementById('totalFineAmount').textContent = 
                totalFine.toLocaleString('vi-VN') + ' VNĐ';
            
            document.getElementById('fineAmount').value = totalFine;
            document.getElementById('amountInWords').textContent = numberToWords(totalFine);
            
            // Cập nhật hidden input
            const violationNames = selectedViolations.map(v => v.name);
            document.getElementById('selectedViolations').value = violationNames.join(', ');
        }
        
        // Xử lý chọn vi phạm
        document.querySelectorAll('.violation-item').forEach(item => {
            item.addEventListener('click', function() {
                const code = this.dataset.code;
                const name = this.dataset.name;
                const fine = parseInt(this.dataset.fine);
                
                const index = selectedViolations.findIndex(v => v.code === code);
                
                if (index === -1) {
                    // Chưa chọn, thêm vào
                    selectedViolations.push({ code, name, fine });
                    this.classList.add('selected');
                    totalFine += fine;
                } else {
                    // Đã chọn, bỏ chọn
                    selectedViolations.splice(index, 1);
                    this.classList.remove('selected');
                    totalFine -= fine;
                }
                
                updateTotal();
            });
        });
        
        // Xử lý thay đổi mức phạt thủ công
        document.getElementById('fineAmount').addEventListener('input', function() {
            const manualFine = parseInt(this.value) || 0;
            document.getElementById('amountInWords').textContent = numberToWords(manualFine);
        });
        
        // Xem trước in ấn
        function printForm() {
            // Validate form
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
                return;
            }
            
            if (selectedViolations.length === 0) {
                alert('Vui lòng chọn ít nhất một loại vi phạm!');
                return;
            }
            
            // Mở cửa sổ xem trước
            window.print();
        }
        
        // Auto format biển số
        document.querySelector('input[name="license_plate"]').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            value = value.replace(/[^A-Z0-9-]/g, '');
            e.target.value = value;
        });
        
        // Auto format số điện thoại
        document.querySelector('input[name="violator_phone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
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
        
        // Initialize
        updateTotal();
    </script>
</body>
</html>