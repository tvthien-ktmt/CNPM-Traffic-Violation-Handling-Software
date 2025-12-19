<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/controllers/AuthController.php';
AuthController::checkAuth();

// Lấy dữ liệu từ controller
$violations = $violations ?? [];
$totalPages = $totalPages ?? 1;
$page = $_GET['page'] ?? 1;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách vi phạm - Cán bộ CSGT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" 
                       class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-list text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Danh sách biên bản</h1>
                        <p class="text-sm text-gray-600">Tổng số: <?php echo count($violations); ?> biên bản</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/traffic/app/controllers/OfficerController.php?action=addViolation" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i> Lập biên bản mới
                    </a>
                    <a href="/traffic/app/controllers/OfficerController.php?action=searchViolation" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i> Tra cứu
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Thông báo -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Danh sách biên bản -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-800">Biên bản đã lập</h2>
            </div>
            
            <?php if (empty($violations)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Chưa có biên bản nào</p>
                    <a href="/traffic/app/controllers/OfficerController.php?action=addViolation" 
                       class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Lập biên bản đầu tiên
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã VP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Biển số</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Địa điểm</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền phạt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($violations as $violation): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-medium text-gray-900"><?php echo $violation['ma_vi_pham']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold"><?php echo $violation['bien_so']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p><?php echo date('d/m/Y', strtotime($violation['thoi_gian_vi_pham'])); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('H:i', strtotime($violation['thoi_gian_vi_pham'])); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="max-w-xs truncate"><?php echo $violation['dia_diem']; ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-red-600">
                                        <?php echo number_format($violation['muc_phat'], 0, ',', '.'); ?> VNĐ
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'Chưa xử lý' => 'bg-yellow-100 text-yellow-800',
                                        'Đã thanh toán' => 'bg-green-100 text-green-800',
                                        'Đã xử lý' => 'bg-blue-100 text-blue-800',
                                        'Đã hủy' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$violation['trang_thai']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $violation['trang_thai']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="/traffic/app/controllers/OfficerController.php?action=viewViolation&id=<?php echo $violation['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/traffic/app/controllers/OfficerController.php?action=exportPDF&id=<?php echo $violation['id']; ?>" 
                                           class="text-green-600 hover:text-green-900" title="Xuất PDF" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="#" onclick="updateStatus(<?php echo $violation['id']; ?>)" 
                                           class="text-yellow-600 hover:text-yellow-900" title="Cập nhật trạng thái">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-700">
                                Trang <span class="font-medium"><?php echo $page; ?></span> / <span class="font-medium"><?php echo $totalPages; ?></span>
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?action=violationsList&page=<?php echo $page - 1; ?>" class="px-3 py-1 border rounded text-sm">
                                    <i class="fas fa-chevron-left mr-1"></i> Trang trước
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?action=violationsList&page=<?php echo $page + 1; ?>" class="px-3 py-1 border rounded text-sm">
                                    Trang sau <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal cập nhật trạng thái -->
    <div id="statusModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <form method="POST" action="/traffic/app/controllers/OfficerController.php?action=updateStatus">
                <input type="hidden" id="violationId" name="id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cập nhật trạng thái</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="Chưa xử lý">Chưa xử lý</option>
                        <option value="Đã thanh toán">Đã thanh toán</option>
                        <option value="Đã xử lý">Đã xử lý</option>
                        <option value="Đã hủy">Đã hủy</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Hủy
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateStatus(id) {
            document.getElementById('violationId').value = id;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
        
        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>