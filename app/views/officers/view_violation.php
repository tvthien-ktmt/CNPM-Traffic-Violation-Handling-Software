<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/controllers/AuthController.php';
AuthController::checkAuth();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết biên bản - Cán bộ CSGT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="/traffic/app/controllers/OfficerController.php?action=violationsList" 
                       class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-invoice text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Chi tiết biên bản</h1>
                        <p class="text-sm text-gray-600">Số: <?php echo $violation['ma_vi_pham']; ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/traffic/app/controllers/OfficerController.php?action=exportPDF&id=<?php echo $violation['id']; ?>" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" target="_blank">
                        <i class="fas fa-file-pdf mr-2"></i> Xuất PDF
                    </a>
                    <a href="#" onclick="window.print()" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-print mr-2"></i> In biên bản
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Thông tin chính -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin biên bản</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Số biên bản:</p>
                                <p class="font-medium"><?php echo $violation['ma_vi_pham']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Ngày lập:</p>
                                <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($violation['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Trạng thái:</p>
                                <?php
                                $statusColors = [
                                    'Chưa xử lý' => 'bg-yellow-100 text-yellow-800',
                                    'Đã thanh toán' => 'bg-green-100 text-green-800',
                                    'Đã xử lý' => 'bg-blue-100 text-blue-800',
                                    'Đã hủy' => 'bg-red-100 text-red-800'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColors[$violation['trang_thai']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $violation['trang_thai']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin phương tiện</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Biển số xe:</p>
                                <p class="font-medium text-xl"><?php echo $violation['bien_so']; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Loại phương tiện:</p>
                                <p class="font-medium"><?php echo $violation['loai_xe'] ?? 'Chưa xác định'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin người vi phạm -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin người vi phạm</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Họ và tên:</p>
                                <p class="font-medium"><?php echo $violation['ho_ten'] ?? 'Chưa xác định'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Số CCCD/CMND:</p>
                                <p class="font-medium"><?php echo $violation['cccd'] ?? 'Chưa xác định'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Số điện thoại:</p>
                                <p class="font-medium"><?php echo $violation['sdt'] ?? 'Chưa xác định'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Địa chỉ:</p>
                                <p class="font-medium"><?php echo $violation['dia_chi'] ?? 'Chưa xác định'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin vi phạm -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin vi phạm</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600">Địa điểm vi phạm:</p>
                        <p class="font-medium"><?php echo $violation['dia_diem']; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Thời gian vi phạm:</p>
                        <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($violation['thoi_gian_vi_pham'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Loại lỗi vi phạm:</p>
                        <p class="font-medium"><?php echo $violation['loi_vi_pham'] ?? 'Chưa xác định'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Mô tả chi tiết:</p>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-700"><?php echo nl2br($violation['mo_ta'] ?? 'Không có mô tả'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Thông tin xử phạt -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Thông tin xử phạt</h3>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">Tổng số tiền phạt:</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo number_format($violation['muc_phat'], 0, ',', '.'); ?> VNĐ</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cán bộ xử lý:</p>
                        <p class="font-medium"><?php echo $_SESSION['ho_ten']; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $_SESSION['ma_can_bo']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>