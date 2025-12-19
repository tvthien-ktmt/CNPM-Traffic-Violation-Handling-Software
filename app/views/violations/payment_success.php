<?php
session_start();
$success = $_GET['success'] ?? ($_SESSION['last_payment']['success'] ?? 0);
$paymentCode = $_GET['payment_code'] ?? ($_SESSION['last_payment']['payment_code'] ?? '');
$licensePlate = $_GET['license_plate'] ?? ($_SESSION['last_payment']['license_plate'] ?? '');
$amount = $_GET['amount'] ?? ($_SESSION['last_payment']['amount'] ?? 0);
$message = $_GET['message'] ?? ($_SESSION['last_payment']['message'] ?? '');

// Xóa session sau khi sử dụng
unset($_SESSION['last_payment']);

include __DIR__ . '/violations_header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($success == 1): ?>
                <!-- THÀNH CÔNG -->
                <div class="card border-success shadow">
                    <div class="card-header bg-success text-white py-3">
                        <h4 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i> 
                            THANH TOÁN THÀNH CÔNG
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="success-icon mb-3">
                                <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-success">Cảm ơn bạn đã thanh toán!</h3>
                            <p class="text-muted">Vi phạm của bạn đã được thanh toán thành công và sẽ không hiển thị trong các lần tra cứu tiếp theo.</p>
                        </div>
                        
                        <div class="alert alert-success border-success">
                            <h5 class="alert-heading">
                                <i class="fas fa-receipt me-2"></i>Thông tin biên lai
                            </h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Mã biên lai:</strong><br>
                                    <span class="text-primary fw-bold fs-5"><?= htmlspecialchars($paymentCode) ?></span></p>
                                    
                                    <p><strong>Biển số xe:</strong><br>
                                    <span class="fs-5"><?= htmlspecialchars($licensePlate) ?></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Số tiền:</strong><br>
                                    <span class="text-danger fw-bold fs-4"><?= number_format($amount, 0, ',', '.') ?> VND</span></p>
                                    
                                    <p><strong>Thời gian:</strong><br>
                                    <span><?= date('d/m/Y H:i:s') ?></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-details mb-4">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Chi tiết giao dịch
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="40%">Trạng thái:</th>
                                                <td><span class="badge bg-success fs-6">ĐÃ THANH TOÁN</span></td>
                                            </tr>
                                            <tr>
                                                <th>Phương thức:</th>
                                                <td>VNPay</td>
                                            </tr>
                                            <tr>
                                                <th>Ngày thanh toán:</th>
                                                <td><?= date('d/m/Y') ?></td>
                                            </tr>
                                            <tr>
                                                <th>Giờ thanh toán:</th>
                                                <td><?= date('H:i:s') ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="instructions alert alert-info">
                            <h6><i class="fas fa-exclamation-circle me-2"></i>Lưu ý quan trọng:</h6>
                            <ul class="mb-0">
                                <li>Biên lai này là bằng chứng thanh toán, vui lòng lưu giữ cẩn thận</li>
                                <li>Vi phạm đã thanh toán sẽ không hiển thị trong lần tra cứu tiếp theo</li>
                                <li>Nếu cần hỗ trợ, vui lòng liên hệ: 1900 1234</li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="/traffic/app/views/violations/search.php" class="btn btn-primary btn-lg me-md-3">
                                    <i class="fas fa-search me-2"></i>Tra Cứu Mới
                                </a>
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-lg me-md-3">
                                    <i class="fas fa-print me-2"></i>In Biên Lai
                                </button>
                                <a href="/traffic/app/views/violations/history.php?bien_so=<?= urlencode($licensePlate) ?>" 
                                   class="btn btn-success btn-lg">
                                    <i class="fas fa-history me-2"></i>Xem Lịch Sử
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center">
                        <small>
                            <i class="fas fa-shield-alt me-1"></i>
                            Giao dịch được bảo mật bởi VNPay | 
                            Mọi thắc mắc vui lòng liên hệ: cskh@traffic.gov.vn
                        </small>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- THẤT BẠI -->
                <div class="card border-danger shadow">
                    <div class="card-header bg-danger text-white py-3">
                        <h4 class="mb-0">
                            <i class="fas fa-times-circle me-2"></i> 
                            THANH TOÁN THẤT BẠI
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="error-icon mb-3">
                                <i class="fas fa-times-circle text-danger" style="font-size: 80px;"></i>
                            </div>
                            <h3 class="text-danger">Thanh toán không thành công!</h3>
                        </div>
                        
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Lỗi thanh toán
                            </h5>
                            <p class="mb-0"><?= htmlspecialchars(urldecode($message)) ?></p>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Thông tin giao dịch
                                </h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Biển số xe:</strong> <?= htmlspecialchars($licensePlate) ?></p>
                                <p><strong>Mã giao dịch:</strong> <?= htmlspecialchars($paymentCode) ?></p>
                                <p><strong>Thời gian:</strong> <?= date('d/m/Y H:i:s') ?></p>
                            </div>
                        </div>
                        
                        <div class="instructions alert alert-warning">
                            <h6><i class="fas fa-lightbulb me-2"></i>Gợi ý khắc phục:</h6>
                            <ul class="mb-0">
                                <li>Kiểm tra số dư tài khoản/thẻ của bạn</li>
                                <li>Đảm bảo thông tin thẻ chính xác</li>
                                <li>Thử lại với phương thức thanh toán khác</li>
                                <li>Liên hệ ngân hàng để được hỗ trợ</li>
                            </ul>
                        </div>
                        
                        <div class="text-center mt-4">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <a href="/traffic/app/views/violations/search.php" class="btn btn-primary btn-lg me-md-3">
                                    <i class="fas fa-arrow-left me-2"></i>Quay Lại Tra Cứu
                                </a>
                                <?php if (!empty($licensePlate)): ?>
                                <a href="/traffic/app/views/violations/search.php?license_plate=<?= urlencode($licensePlate) ?>" 
                                   class="btn btn-warning btn-lg">
                                    <i class="fas fa-redo me-2"></i>Thử Lại Thanh Toán
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center">
                        <small>
                            Cần hỗ trợ? Gọi ngay: 1900 1234 (Miễn phí) hoặc email: support@traffic.gov.vn
                        </small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.success-icon, .error-icon {
    animation: bounceIn 1s ease;
}
@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}
.card {
    border-radius: 15px;
    overflow: hidden;
}
.btn-lg {
    padding: 12px 30px;
    font-size: 16px;
    border-radius: 8px;
}
@media print {
    .btn, .instructions, .card-footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php include __DIR__ . '/violations_footer.php'; ?>