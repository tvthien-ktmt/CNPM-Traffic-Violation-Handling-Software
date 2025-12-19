
<?php
session_start();

// Kiểm tra dữ liệu từ camera
if (!isset($_SESSION['camera_violation_data'])) {
    header('Location: /traffic/app/controllers/CameraController.php?action=index');
    exit();
}

$cameraData = $_SESSION['camera_violation_data'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoàn tất Biên bản từ Camera</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-file-pen me-2"></i>
                    Hoàn tất Biên bản từ Camera
                </h4>
            </div>
            <div class="card-body">
                <!-- Thông tin từ camera -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-camera me-2"></i>Thông tin từ Camera</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Biển số:</strong> <?php echo htmlspecialchars($cameraData['license_plate']); ?></p>
                            <p><strong>Loại xe:</strong> <?php echo htmlspecialchars($cameraData['vehicle_type']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Loại vi phạm:</strong> <?php echo htmlspecialchars($cameraData['violation_type']); ?></p>
                            <p><strong>Mức phạt:</strong> <?php echo number_format($cameraData['fine_amount'], 0, ',', '.'); ?> VNĐ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Form hoàn tất thông tin -->
                <form action="/traffic/app/controllers/OfficerController.php?action=processCameraViolation" method="POST">
                    <input type="hidden" name="camera_data" value='<?php echo json_encode($cameraData); ?>'>
                    
                    <div class="mb-3">
                        <label class="form-label">Họ tên người vi phạm *</label>
                        <input type="text" class="form-control" name="violator_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số CCCD/CMND *</label>
                        <input type="text" class="form-control" name="violator_id" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" class="form-control" name="violator_phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" name="violator_address">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Chi tiết vi phạm</label>
                        <textarea class="form-control" name="violation_content" rows="3">
Biển số: <?php echo htmlspecialchars($cameraData['license_plate']); ?>
Loại vi phạm: <?php echo htmlspecialchars($cameraData['violation_type']); ?>
Thời gian: <?php echo htmlspecialchars($cameraData['violation_time']); ?>
                        </textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="/traffic/app/controllers/CameraController.php?action=view&id=<?php echo $cameraData['camera_id']; ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại Camera
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-pdf me-2"></i>Xuất Biên Bản PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
