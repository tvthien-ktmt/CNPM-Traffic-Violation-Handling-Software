<?php
/**
 * FILE: app/views/violations/search.php
 * TÍCH HỢP SEPAY - HOÀN CHỈNH
 * 
 * Chỉ cần:
 * 1. Copy toàn bộ file này
 * 2. Thay thế file search.php cũ
 * 3. Đảm bảo đã cấu hình sepay_config.php
 * 4. Test thanh toán!
 */

// ==================== KẾT NỐI DATABASE ====================
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Violation.php';

// Biến xử lý
$violations = [];
$licensePlate = '';
$searchPerformed = false;
$inputPlate = '';
$vehicleType = '1';
$totalAmount = 0;
$unpaidAmount = 0;
$unpaidCount = 0;
$errorMessage = '';
$databaseConnected = false;
$searchInfo = '';

// ==================== KIỂM TRA KẾT NỐI DATABASE ====================
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $databaseConnected = true;
} catch (Exception $e) {
    $errorMessage = "Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.";
}

// ==================== XỬ LÝ FORM TRA CỨU ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchPerformed = true;
    $vehicleType = $_POST['vehicle_type'] ?? '1';
    $inputPlate = $_POST['license_plate'] ?? '';
    
    $licensePlate = strtoupper(str_replace(['-', '.', ' '], '', trim($inputPlate)));
    
    if (empty($licensePlate)) {
        $errorMessage = "Vui lòng nhập biển số xe!";
    } elseif (!$databaseConnected) {
        $errorMessage = "Hệ thống đang bảo trì. Không thể kết nối database.";
    } else {
        $violationModel = new Violation();
        
        if (!$violationModel->validateLicensePlateFormat($licensePlate, $vehicleType)) {
            $typeNames = [
                '1' => 'xe ô tô (ví dụ: 89H0227, 29C89082, 50H56240)',
                '2' => 'xe máy (ví dụ: 29BC04329, 30H123456, 29A12345)',
                '3' => 'xe điện (ví dụ: 29AB12345)',
                '4' => 'loại xe khác'
            ];
            
            $errorMessage = "Biển số <strong>$licensePlate</strong> không đúng định dạng cho " . 
                           ($typeNames[$vehicleType] ?? 'loại xe đã chọn') . ". Vui lòng kiểm tra lại!";
        } else {
            try {
                $dbViolations = $violationModel->getViolationsByLicensePlate($licensePlate, $vehicleType);
                
                if (!empty($dbViolations)) {
                    foreach ($dbViolations as $violation) {
                        $time = 'N/A';
                        if (!empty($violation['thoi_gian_vi_pham'])) {
                            try {
                                $date = new DateTime($violation['thoi_gian_vi_pham']);
                                $time = $date->format('d/m/Y - H:i:s');
                            } catch (Exception $e) {
                                $time = $violation['thoi_gian_vi_pham'];
                            }
                        }
                        
                        $vehicle_type = $violation['loai_xe'] ?? 'Chưa xác định';
                        $fine_amount = !empty($violation['muc_phat']) && $violation['muc_phat'] > 0 
                                      ? (int)$violation['muc_phat'] 
                                      : 500000;
                        $trang_thai = $violation['trang_thai'] ?? 'Chưa xử lý';
                        $violation_id = $violation['id'] ?? null;
                        
                        $violations[] = [
                            'id' => $violation_id,
                            'time' => $time,
                            'plate' => $violation['bien_so'] ?? $licensePlate,
                            'plate_color' => $violation['mau_bien'] ?? 'Trắng',
                            'vehicle_type' => $vehicle_type,
                            'violation_type' => $violation['ten_loi'] ?? 'Vi phạm giao thông',
                            'fine_amount' => $fine_amount,
                            'location' => $violation['dia_diem'] ?? 'Hà Nội',
                            'trang_thai' => $trang_thai
                        ];
                        
                        $totalAmount += $fine_amount;
                        
                        $status_lower = mb_strtolower(trim($trang_thai), 'UTF-8');
                        $is_paid = (strpos($status_lower, 'thanh toán') !== false || 
                                    strpos($status_lower, 'paid') !== false ||
                                    strpos($status_lower, 'completed') !== false);
                        
                        if (!$is_paid) {
                            $unpaidAmount += $fine_amount;
                            $unpaidCount++;
                        }
                    }
                    
                    $typeLabels = ['1' => 'Xe ô tô', '2' => 'Xe máy', '3' => 'Xe điện', '4' => 'Loại khác'];
                    $searchInfo = "Tìm kiếm: <strong>" . ($typeLabels[$vehicleType] ?? 'Tất cả') . 
                                "</strong> | Biển số: <strong>$licensePlate</strong>";
                } else {
                    $typeLabels = ['1' => 'xe ô tô', '2' => 'xe máy', '3' => 'xe điện', '4' => 'loại xe khác'];
                    $searchInfo = "Tìm kiếm: <strong>" . ($typeLabels[$vehicleType] ?? 'Tất cả') . 
                                "</strong> | Biển số: <strong>$licensePlate</strong>";
                }
            } catch (Exception $e) {
                $errorMessage = "Lỗi khi truy vấn dữ liệu: " . $e->getMessage();
            }
        }
    }
}

// ==================== HEADER ====================
include __DIR__ . '/../violations/violations_header.php';
?>

<!-- ==================== CSS HOÀN CHỈNH ==================== -->
<style>
/* CSS GỐC */
.search-container {
    margin: 0 auto;
    max-width: 800px;
    background: #ffffff;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.vehicle-options {
    display: flex;
    justify-content: space-around;
    margin-bottom: 25px;
    gap: 15px;
}

.vehicle-option {
    flex: 1;
    padding: 20px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.vehicle-option:hover {
    border-color: #1e88e5;
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(30, 136, 229, 0.2);
}

.vehicle-option.selected {
    border-color: #1e88e5;
    background: #e3f2fd;
}

.vehicle-icon {
    font-size: 40px;
    margin-bottom: 10px;
    color: #424242;
}

.search-input {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    border: 2px solid #bdbdbd;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
    letter-spacing: 1px;
    color: #212121;
    background: #fafafa;
}

.search-input:focus {
    outline: none;
    border-color: #1e88e5;
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
    background: white;
}

.search-btn {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    font-weight: bold;
    color: white;
    background: #1e88e5;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(30, 136, 229, 0.3);
    background: #1976d2;
}

.violation-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-left: 4px solid #f44336;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.violation-card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transform: translateX(5px);
}

.violation-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    padding: 10px;
    background: #f5f5f5;
    border-radius: 6px;
}

.info-label {
    font-size: 12px;
    color: #616161;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.info-value {
    font-size: 16px;
    font-weight: bold;
    color: #212121;
}

.violation-type {
    color: #d32f2f;
}

.payment-btn {
    display: inline-block;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: bold;
    color: white;
    background: #4caf50;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(76, 175, 80, 0.3);
    cursor: pointer;
}

.payment-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(76, 175, 80, 0.4);
    color: white;
    background: #388e3c;
}

.history-btn {
    display: inline-block;
    padding: 15px 40px;
    font-size: 18px;
    font-weight: bold;
    color: white;
    background: #ff9800;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(255, 152, 0, 0.3);
    margin-left: 15px;
}

.history-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(255, 152, 0, 0.4);
    color: white;
    background: #f57c00;
}

/* CSS CHO SEPAY */
.payment-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.payment-modal.show {
    display: flex !important;
    opacity: 1 !important;
}

.payment-content {
    background: white;
    width: 90%;
    max-width: 600px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.payment-header {
    background: #1e88e5;
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 10;
}

.payment-header h4 {
    margin: 0;
    font-weight: bold;
    font-size: 18px;
}

.close-payment {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    transition: transform 0.3s;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-payment:hover {
    transform: scale(1.2);
}

.payment-body {
    padding: 25px;
}

.payment-info {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.payment-summary {
    background: #fff3e0;
    border: 2px solid #ffcc80;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
}

.payment-option {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option:hover {
    border-color: #1e88e5;
    background: #e3f2fd;
}

.payment-option.selected {
    border-color: #1e88e5;
    background: #bbdefb;
}

#qrCodeSection {
    display: none;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.qr-container {
    text-align: center;
    margin: 20px 0;
}

.qr-code-frame {
    display: inline-block;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bank-transfer-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin: 15px 0;
}

.important-notes {
    background: #fff3e0;
    border: 2px solid #ffb74d;
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
}

.payment-status {
    text-align: center;
    padding: 20px;
    margin-top: 20px;
    border-top: 1px solid #dee2e6;
}

.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-animation 0.75s linear infinite;
}

@keyframes spinner-animation {
    to { transform: rotate(360deg); }
}

#paymentLoading {
    display: none;
    text-align: center;
    padding: 30px;
}

#paymentSuccess {
    display: none;
    text-align: center;
    padding: 30px;
}

.payment-footer {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    background: #f5f5f5;
    border-top: 1px solid #e0e0e0;
    gap: 10px;
}

.btn-pay {
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    flex: 1;
}

.btn-pay-confirm {
    background: #4caf50;
    color: white;
}

.btn-pay-confirm:hover {
    background: #388e3c;
    transform: translateY(-2px);
}

.btn-pay-cancel {
    background: #757575;
    color: white;
}

.btn-pay-cancel:hover {
    background: #616161;
}

/* Utilities */
.text-center { text-align: center; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-3 { margin-top: 1rem; }
.mt-4 { margin-top: 1.5rem; }
.text-primary { color: #1e88e5; }
.text-danger { color: #dc3545; }
.text-success { color: #28a745; }
.text-muted { color: #6c757d; }
.font-weight-bold { font-weight: 700; }

@media (max-width: 768px) {
    .payment-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .payment-footer {
        flex-direction: column;
    }
    
    .vehicle-options {
        flex-direction: column;
    }
}
</style>

<!-- ==================== HTML ==================== -->
<div class="container">
    <div class="search-container">
        <h2 class="text-center mb-4" style="color: #004aad; font-weight: bold;">
            <i class="fas fa-search" style="margin-right: 10px;"></i>Tra Cứu Vi Phạm Giao Thông
        </h2>
        
        <div style="text-align: center; padding: 10px; margin-bottom: 20px; font-size: 14px; border-radius: 6px; <?= $databaseConnected ? 'background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;' : 'background: #ffebee; color: #c62828; border: 1px solid #ffcdd2;' ?>">
            <i class="fas fa-database" style="margin-right: 8px;"></i>
            <?php if ($databaseConnected): ?>
                Dữ liệu được cập nhật đến ngày 28/11/2025
            <?php else: ?>
                Không thể kết nối database
            <?php endif; ?>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
            <div style="background: #f44336; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if ($searchPerformed && !empty($searchInfo) && empty($errorMessage)): ?>
            <div style="background: #e3f2fd; border: 1px solid #bbdefb; color: #0d47a1; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                <?= $searchInfo ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="tracuu">
            <div class="vehicle-options">
                <?php
                $vehicleTypes = [
                    '1' => ['icon' => '🚗', 'name' => 'Xe Ô tô'],
                    '2' => ['icon' => '🏍️', 'name' => 'Xe Máy'],
                    '3' => ['icon' => '🛵', 'name' => 'Xe Điện'],
                    '4' => ['icon' => '🚚', 'name' => 'Loại khác']
                ];
                
                foreach ($vehicleTypes as $value => $type):
                    $selected = (!$searchPerformed && $value == '1') || ($searchPerformed && $vehicleType == $value);
                ?>
                    <div class="vehicle-option <?= $selected ? 'selected' : '' ?>" data-value="<?= $value ?>">
                        <input type="radio" name="vehicle_type" value="<?= $value ?>" <?= $selected ? 'checked' : '' ?> hidden>
                        <div class="vehicle-icon"><?= $type['icon'] ?></div>
                        <div style="font-weight: bold;"><?= $type['name'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-4">
                <input type="text" 
                       name="license_plate" 
                       placeholder="NHẬP BIỂN SỐ XE (không dấu gạch)" 
                       value="<?= htmlspecialchars($inputPlate) ?>"
                       required 
                       class="search-input"
                       <?= !$databaseConnected ? 'disabled' : '' ?>
                       maxlength="20">
                <div class="text-center" style="margin-top: 10px;">
                    <small class="text-muted">Nhập biển số xe, ví dụ: 89H0227 hoặc 29BC04329</small>
                </div>
            </div>
            
            <button type="submit" 
                    class="search-btn"
                    <?= !$databaseConnected ? 'disabled' : '' ?>>
                <i class="fas fa-search" style="margin-right: 8px;"></i>
                <?= $databaseConnected ? 'Tra Cứu Dữ Liệu' : 'HỆ THỐNG BẢO TRÌ' ?>
            </button>
        </form>
        
        <!-- KẾT QUẢ TRA CỨU -->
        <div id="ketquatracuu" class="mt-4">
            <?php if ($searchPerformed && empty($errorMessage) && !empty($violations)): ?>
                <div style="background: #f44336; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                    Tìm thấy <?= count($violations) ?> vi phạm - 
                    Tổng tiền: <strong><?= number_format($totalAmount, 0, ',', '.') ?> VND</strong>
                    
                    <?php if ($unpaidCount > 0): ?>
                        <div style="margin-top: 10px;">
                            <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 4px;">
                                <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                Còn <?= $unpaidCount ?> vi phạm chưa thanh toán: 
                                <strong><?= number_format($unpaidAmount, 0, ',', '.') ?> VND</strong>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- DANH SÁCH VI PHẠM -->
                <?php foreach ($violations as $index => $violation): 
                    $status_lower = mb_strtolower(trim($violation['trang_thai']), 'UTF-8');
                    $is_paid = (strpos($status_lower, 'thanh toán') !== false || 
                                strpos($status_lower, 'paid') !== false ||
                                strpos($status_lower, 'completed') !== false);
                    $payment_id = $violation['id'] ?? 0;
                ?>
                    <div class="violation-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h5 style="margin: 0;">
                                <i class="fas fa-file-alt" style="margin-right: 8px;"></i>Vi phạm #<?= $index + 1 ?>
                            </h5>
                            <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; <?= $is_paid ? 'background: #e8f5e9; color: #2e7d32;' : 'background: #ffebee; color: #c62828;' ?>">
                                <?= htmlspecialchars($violation['trang_thai']) ?>
                            </span>
                        </div>
                        
                        <div class="violation-info">
                            <div class="info-item">
                                <div class="info-label">Thời gian</div>
                                <div class="info-value"><?= htmlspecialchars($violation['time']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Biển số</div>
                                <div class="info-value"><?= htmlspecialchars($violation['plate']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Loại vi phạm</div>
                                <div class="info-value violation-type"><?= htmlspecialchars($violation['violation_type']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Tiền phạt</div>
                                <div class="info-value" style="color: #d32f2f;"><?= number_format($violation['fine_amount'], 0, ',', '.') ?> VND</div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <?php if ($is_paid): ?>
                                <span style="background: #e8f5e9; color: #2e7d32; padding: 8px 16px; border-radius: 6px; font-weight: bold;">
                                    <i class="fas fa-check-circle" style="margin-right: 5px;"></i>Đã thanh toán
                                </span>
                            <?php elseif ($payment_id > 0): ?>
                                <button type="button" 
                                        class="btn-pay-single payment-btn" 
                                        style="padding: 10px 20px; font-size: 14px;"
                                        data-violation-id="<?= $payment_id ?>"
                                        data-violation-type="<?= htmlspecialchars($violation['violation_type']) ?>"
                                        data-amount="<?= $violation['fine_amount'] ?>"
                                        data-bien-so="<?= htmlspecialchars($licensePlate) ?>"
                                        data-time="<?= htmlspecialchars($violation['time']) ?>">
                                    <i class="fas fa-credit-card" style="margin-right: 5px;"></i>Thanh toán vi phạm này
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- TỔNG KẾT -->
                <?php if (count($violations) >= 2): ?>
                <div class="text-center mt-4" style="padding: 30px; background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%); border-radius: 12px; border: 2px solid #ffa726;">
                    <h5 style="color: #e65100; margin-bottom: 20px;">
                        <i class="fas fa-receipt" style="margin-right: 8px;"></i>TỔNG KẾT VI PHẠM
                    </h5>
                    
                    <?php if ($unpaidCount > 0): ?>
                        <button type="button" 
                                class="payment-btn btn-pay-all"
                                data-bien-so="<?= htmlspecialchars($licensePlate) ?>"
                                data-total-amount="<?= $unpaidAmount ?>"
                                data-count="<?= $unpaidCount ?>"
                                style="font-size: 18px; padding: 16px 45px; margin-bottom: 10px;">
                            <i class="fas fa-credit-card" style="margin-right: 8px;"></i>THANH TOÁN TẤT CẢ (<?= $unpaidCount ?> VI PHẠM)
                        </button>
                        <br>
                    <?php else: ?>
                        <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <div style="font-size: 36px; color: #2e7d32; margin-bottom: 8px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 style="color: #2e7d32;">Tất cả vi phạm đã được thanh toán!</h5>
                        </div>
                    <?php endif; ?>

                    <a href="/traffic/app/views/violations/history.php?bien_so=<?= urlencode($licensePlate) ?>" 
                       class="history-btn"
                       style="font-size: 16px; padding: 14px 35px;">
                        <i class="fas fa-history" style="margin-right: 8px;"></i>XEM LỊCH SỬ
                    </a>
                </div>
                <?php endif; ?>
                
            <?php elseif ($searchPerformed && empty($errorMessage) && empty($violations)): ?>
                <div style="text-align: center; padding: 40px; background: #e8f5e9; border: 2px solid #4caf50; border-radius: 8px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #4caf50; margin-bottom: 15px;"></i>
                    <h4 style="color: #2e7d32;">Không tìm thấy vi phạm</h4>
                    <p>Biển số <strong><?= htmlspecialchars($licensePlate) ?></strong> không có vi phạm nào.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==================== MODAL THANH TOÁN SEPAY ==================== -->
<div id="paymentModal" class="payment-modal">
    <div class="payment-content">
        <div class="payment-header">
            <h4><i class="fas fa-credit-card" style="margin-right: 8px;"></i>THANH TOÁN VI PHẠM</h4>
            <button type="button" class="close-payment">&times;</button>
        </div>
        
        <div class="payment-body">
            <div class="payment-info">
                <h5 id="paymentTitle" style="color: #1e88e5; margin-bottom: 15px;">Thanh toán vi phạm</h5>
                <p><strong>Biển số:</strong> <span id="paymentPlate" style="color: #004aad;">N/A</span></p>
                <p><strong>Loại vi phạm:</strong> <span id="paymentViolationType" style="color: #dc3545;">N/A</span></p>
                <p><strong>Số lượng:</strong> <span id="paymentCount">0 vi phạm</span></p>
            </div>
            
            <div class="payment-summary">
                <h6 style="color: #e65100; margin-bottom: 15px;">
                    <i class="fas fa-receipt" style="margin-right: 8px;"></i>TỔNG KẾT
                </h6>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <span>Tổng tiền phạt:</span>
                    <span id="paymentSubtotal" style="color: #dc3545; font-weight: bold;">0 VND</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                    <span>Phí xử lý:</span>
                    <span>0 VND</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; margin-top: 10px; font-weight: bold; font-size: 18px;">
                    <span>Tổng thanh toán:</span>
                    <span id="paymentTotal" style="color: #dc3545;">0 VND</span>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h6 style="color: #1e88e5; margin-bottom: 15px;">
                    <i class="fas fa-wallet" style="margin-right: 8px;"></i>PHƯƠNG THỨC THANH TOÁN
                </h6>
                
                <div class="payment-option selected" data-method="sepay">
                    <div style="font-size: 24px; margin-right: 15px;">🏦</div>
                    <div style="flex: 1;">
                        <h6 style="margin: 0 0 5px 0;">SePay - Chuyển khoản ngân hàng</h6>
                        <small style="color: #616161;">Quét QR hoặc chuyển khoản thủ công</small>
                    </div>
                    <input type="radio" name="paymentMethod" value="sepay" checked>
                </div>
            </div>
            
            <!-- QR Code Section -->
            <div id="qrCodeSection"></div>
            
            <!-- Loading -->
            <div id="paymentLoading">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                <h5 style="color: #1e88e5; margin-top: 15px;">Đang xử lý thanh toán...</h5>
                <p>Vui lòng không đóng trang này</p>
            </div>
            
            <!-- Success -->
            <div id="paymentSuccess"></div>
        </div>
        
        <div class="payment-footer">
            <button type="button" class="btn-pay btn-pay-cancel">Hủy</button>
            <button type="button" class="btn-pay btn-pay-confirm" id="confirmPayment">
                <i class="fas fa-lock" style="margin-right: 8px;"></i>XÁC NHẬN THANH TOÁN
            </button>
        </div>
    </div>
</div>

<!-- ==================== JAVASCRIPT HOÀN CHỈNH ==================== -->
<script>
// ========== BIẾN TOÀN CỤC ==========
let currentPaymentType = 'single';
let currentViolationIds = [];
let currentTotalAmount = 0;
let currentLicensePlate = '';
let currentPaymentGroupId = '';
let currentPaymentCode = '';
let checkInterval = null;
let countdownInterval = null;
let remainingTime = 300; // 5 phút = 300 giây
let pollingCount = 1;
let isPaymentCompleted = false;
let eventSource = null;

// ========== KHỞI TẠO ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Payment] System initialized');
    initializeVehicleSelection();
    initializeSearchForm();
    initializePaymentEvents();
    
    checkUrlForPendingPayment();
    checkRecentPayments();
    
    // Lắng nghe tin nhắn từ manual webhook
    window.addEventListener('message', handleIncomingMessage);
    window.addEventListener('storage', handleStorageChange);
});

// ========== XỬ LÝ TIN NHẮN TỪ MANUAL WEBHOOK ==========
function handleIncomingMessage(event) {
    if (event.data && event.data.type === 'PAYMENT_COMPLETED') {
        console.log('[Payment] Received payment completion message:', event.data);
        
        if (event.data.payment_id === currentPaymentGroupId) {
            // Nếu đang mở modal thanh toán này
            clearInterval(checkInterval);
            clearInterval(countdownInterval);
            isPaymentCompleted = true;
            
            fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${event.data.payment_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status === 'completed') {
                        showPaymentSuccess(data);
                        showGlobalNotification('✅ Thanh toán đã được xác nhận qua Manual Webhook!', 'success');
                        updateViolationsListRealTime(data.violation_ids || []);
                    }
                });
        } else {
            // Nếu là payment khác, hiển thị thông báo
            showGlobalNotification('📢 Một thanh toán khác vừa được xác nhận', 'info');
        }
    }
}

// ========== XỬ LÝ THAY ĐỔI STORAGE ==========
function handleStorageChange(event) {
    if (event.key === 'payment_update') {
        try {
            const data = JSON.parse(event.newValue);
            if (data.payment_id && data.action === 'completed') {
                console.log('[Payment] Storage update received:', data);
                showGlobalNotification('🔄 Cập nhật trạng thái thanh toán', 'info');
            }
        } catch (e) {
            console.error('[Payment] Parse storage error:', e);
        }
    }
}

// ========== KIỂM TRA PAYMENT GẦN ĐÂY ==========
function checkRecentPayments() {
    const recentPayments = JSON.parse(localStorage.getItem('recent_payments') || '[]');
    const tenMinutesAgo = Date.now() - (10 * 60 * 1000);
    
    recentPayments.forEach(payment => {
        if (payment.timestamp > tenMinutesAgo && payment.status === 'pending') {
            checkPaymentStatusSilently(payment.id);
        }
    });
}

// ========== KIỂM TRA TRẠNG THÁI ÂM THẦM ==========
function checkPaymentStatusSilently(paymentId) {
    fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status === 'completed') {
                updateRecentPaymentStatus(paymentId, 'completed');
                showGlobalNotification('💡 Một thanh toán trước đó vừa được xác nhận', 'success');
            }
        });
}

// ========== CẬP NHẬT LỊCH SỬ PAYMENT ==========
function updateRecentPaymentStatus(paymentId, status) {
    let recentPayments = JSON.parse(localStorage.getItem('recent_payments') || '[]');
    recentPayments = recentPayments.filter(p => p.id !== paymentId);
    recentPayments.unshift({
        id: paymentId,
        status: status,
        timestamp: Date.now()
    });
    
    if (recentPayments.length > 5) {
        recentPayments = recentPayments.slice(0, 5);
    }
    
    localStorage.setItem('recent_payments', JSON.stringify(recentPayments));
}

// ========== CHỌN LOẠI XE ==========
function initializeVehicleSelection() {
    document.querySelectorAll('.vehicle-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.vehicle-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });
}

// ========== FORM TRA CỨU ==========
function initializeSearchForm() {
    const tracuuForm = document.getElementById('tracuu');
    if (!tracuuForm) return;
    
    tracuuForm.addEventListener('submit', function(e) {
        const plateInput = document.querySelector('input[name="license_plate"]');
        if (!plateInput) return;
        
        const plateValue = plateInput.value.trim();
        if (!plateValue) {
            e.preventDefault();
            alert('Vui lòng nhập biển số xe!');
            plateInput.focus();
            return false;
        }
        
        const cleanPlate = plateValue.replace(/[^A-Z0-9]/gi, '').toUpperCase();
        plateInput.value = cleanPlate;
        
        const searchBtn = document.querySelector('.search-btn');
        if (searchBtn && !searchBtn.disabled) {
            const originalText = searchBtn.innerHTML;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>ĐANG TRA CỨU...';
            searchBtn.disabled = true;
            
            setTimeout(() => {
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;
            }, 5000);
        }
        
        return true;
    });
}

// ========== THANH TOÁN ==========
function initializePaymentEvents() {
    document.querySelectorAll('.btn-pay-single').forEach(button => {
        button.addEventListener('click', function() {
            const violationId = parseInt(this.getAttribute('data-violation-id'));
            const violationType = this.getAttribute('data-violation-type');
            const amount = parseInt(this.getAttribute('data-amount'));
            const bienSo = this.getAttribute('data-bien-so');
            
            console.log('[Payment] Single payment:', {violationId, amount, bienSo});
            
            if (!violationId || amount <= 0) {
                alert('Thông tin thanh toán không hợp lệ!');
                return;
            }
            
            currentPaymentType = 'single';
            currentViolationIds = [violationId];
            currentTotalAmount = amount;
            currentLicensePlate = bienSo;
            
            openPaymentModal({
                title: 'Thanh toán vi phạm đơn lẻ',
                plate: bienSo,
                violationType: violationType,
                count: 1,
                total: amount
            });
        });
    });
    
    const btnPayAll = document.querySelector('.btn-pay-all');
    if (btnPayAll) {
        btnPayAll.addEventListener('click', function() {
            const bienSo = this.getAttribute('data-bien-so');
            const totalAmount = parseInt(this.getAttribute('data-total-amount'));
            const count = parseInt(this.getAttribute('data-count'));
            
            const violationIds = [];
            document.querySelectorAll('.btn-pay-single').forEach(button => {
                const id = parseInt(button.getAttribute('data-violation-id'));
                if (id > 0) violationIds.push(id);
            });
            
            console.log('[Payment] All payment:', {count, totalAmount, ids: violationIds});
            
            if (violationIds.length === 0) {
                alert('Không có vi phạm để thanh toán!');
                return;
            }
            
            currentPaymentType = 'all';
            currentViolationIds = violationIds;
            currentTotalAmount = totalAmount;
            currentLicensePlate = bienSo;
            
            openPaymentModal({
                title: 'Thanh toán tất cả vi phạm',
                plate: bienSo,
                violationType: count + ' vi phạm',
                count: count,
                total: totalAmount
            });
        });
    }
    
    document.querySelectorAll('.close-payment, .btn-pay-cancel').forEach(btn => {
        btn.addEventListener('click', closePaymentModal);
    });
    
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('click', function(e) {
            if (e.target === this) closePaymentModal();
        });
    }
    
    const confirmBtn = document.getElementById('confirmPayment');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', handlePaymentConfirmation);
    }
}

// ========== MỞ MODAL ==========
function openPaymentModal(data) {
    console.log('[Payment] Opening modal:', data);
    
    const paymentModal = document.getElementById('paymentModal');
    if (!paymentModal) {
        console.error('[Payment] Payment modal not found!');
        return;
    }
    
    document.getElementById('paymentTitle').textContent = data.title;
    document.getElementById('paymentPlate').textContent = data.plate;
    document.getElementById('paymentViolationType').textContent = data.violationType;
    document.getElementById('paymentCount').textContent = data.count + ' vi phạm';
    document.getElementById('paymentSubtotal').textContent = formatCurrency(data.total);
    document.getElementById('paymentTotal').textContent = formatCurrency(data.total);
    
    document.getElementById('qrCodeSection').style.display = 'none';
    document.getElementById('qrCodeSection').innerHTML = '';
    document.getElementById('paymentLoading').style.display = 'none';
    document.getElementById('paymentSuccess').style.display = 'none';
    document.getElementById('confirmPayment').style.display = 'block';
    document.querySelector('.btn-pay-cancel').style.display = 'block';
    
    paymentModal.style.display = 'flex';
    setTimeout(() => {
        paymentModal.classList.add('show');
        paymentModal.style.opacity = '1';
    }, 10);
    
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = '15px';
}

// ========== ĐÓNG MODAL ==========
function closePaymentModal() {
    const paymentModal = document.getElementById('paymentModal');
    if (!paymentModal) return;
    
    paymentModal.classList.remove('show');
    paymentModal.style.opacity = '0';
    
    setTimeout(() => {
        paymentModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '0';
    }, 300);
    
    if (checkInterval) {
        clearInterval(checkInterval);
        checkInterval = null;
    }
    
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    
    remainingTime = 300; // Reset thời gian đếm ngược
    pollingCount = 1;
    
    if (eventSource) {
        eventSource.close();
        eventSource = null;
    }
}

// ========== XÁC NHẬN THANH TOÁN ==========
function handlePaymentConfirmation() {
    console.log('[Payment] Confirming payment...', { 
        violation_ids: currentViolationIds, 
        amount: currentTotalAmount, 
        license_plate: currentLicensePlate 
    });
    
    if (currentViolationIds.length === 0 || currentTotalAmount <= 0) {
        alert('Thông tin thanh toán không hợp lệ!');
        return;
    }
    
    document.getElementById('paymentLoading').style.display = 'block';
    document.getElementById('confirmPayment').style.display = 'none';
    document.querySelector('.btn-pay-cancel').style.display = 'none';
    
    fetch('/traffic/app/controllers/PaymentController.php?action=init', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            violation_ids: currentViolationIds,
            license_plate: currentLicensePlate,
            amount: currentTotalAmount
        })
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('[Payment] Payment created:', data);
        
        if (data.success) {
            currentPaymentGroupId = data.payment_id;
            currentPaymentCode = data.payment_code;
            
            document.getElementById('paymentLoading').style.display = 'none';
            showQRCodeSection(data);
            
            sessionStorage.setItem('last_payment_id', currentPaymentGroupId);
            sessionStorage.setItem('last_payment_time', Date.now());
            
            updateRecentPaymentStatus(currentPaymentGroupId, 'pending');
            
            const url = new URL(window.location);
            url.searchParams.set('pending_payment', currentPaymentGroupId);
            window.history.replaceState({}, '', url);
            
            startPaymentCheck(data.payment_code, data.payment_id, data.violation_ids);
            startSSEConnection(data.payment_id);
            
            showGlobalNotification('✅ Đã tạo mã thanh toán. Vui lòng quét QR để thanh toán!', 'info');
        } else {
            throw new Error(data.message || 'Không thể tạo thanh toán');
        }
    })
    .catch(error => {
        console.error('[Payment] Error:', error);
        showGlobalNotification('❌ Lỗi tạo thanh toán: ' + error.message, 'error');
        closePaymentModal();
    });
}

// ========== KẾT NỐI SSE ĐỂ REAL-TIME ==========
function startSSEConnection(paymentId) {
    if (eventSource) {
        eventSource.close();
    }
    
    eventSource = new EventSource(`/traffic/app/controllers/PaymentController.php?action=realtime_status&payment_id=${paymentId}`);
    
    eventSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            console.log('[Payment] SSE message:', data);
            
            if (data.success && data.status === 'completed') {
                clearInterval(checkInterval);
                clearInterval(countdownInterval);
                isPaymentCompleted = true;
                eventSource.close();
                
                fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${paymentId}`)
                    .then(response => response.json())
                    .then(paymentData => {
                        if (paymentData.success && paymentData.status === 'completed') {
                            showPaymentSuccess(paymentData);
                            showGlobalNotification('✅ Thanh toán thành công (SSE)!', 'success');
                            updateViolationsListRealTime(paymentData.violation_ids || []);
                        }
                    });
            }
        } catch (e) {
            console.error('[Payment] SSE parse error:', e);
        }
    };
    
    eventSource.onerror = function(error) {
        console.error('[Payment] SSE error:', error);
        eventSource.close();
    };
}

// ========== HIỂN THỊ QR CODE VÀ ĐẾM NGƯỢC ==========
function showQRCodeSection(paymentData) {
    const qrSection = document.getElementById('qrCodeSection');
    if (!qrSection) {
        console.error('[Payment] QR Code section not found!');
        return;
    }
    
    qrSection.innerHTML = `
        <div style="text-align: center;">
            <h6 style="color: #1e88e5; margin-bottom: 20px;">
                <i class="fas fa-qrcode" style="margin-right: 8px;"></i>QUÉT MÃ QR ĐỂ THANH TOÁN
            </h6>
            
            <div class="countdown-timer" style="margin: 15px 0 20px 0; padding: 15px; background: linear-gradient(135deg, #ff9800, #ff5722); border-radius: 10px; color: white; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);">
                <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="fas fa-clock fa-lg"></i>
                    <h4 style="margin: 0; font-weight: bold; font-size: 22px;">QR SẼ HẾT HẠN SAU</h4>
                </div>
                <div id="countdownDisplay" style="font-size: 36px; font-weight: 900; font-family: 'Courier New', monospace; letter-spacing: 2px;">
                    05:00
                </div>
                <div style="margin-top: 8px; font-size: 14px; opacity: 0.9;">
                    <i class="fas fa-exclamation-triangle"></i> Vui lòng thanh toán trong thời gian này
                </div>
            </div>
            
            <div class="qr-container" style="margin: 20px 0;">
                <div style="display: inline-block; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 2px solid #ff9800;">
                    <img src="${paymentData.qr_code_url}" alt="QR Code" style="max-width: 250px; width: 100%;">
                </div>
            </div>
            
            <div class="bank-transfer-info" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left; border: 1px solid #e0e0e0;">
                <h6 style="color: #1e88e5; margin-bottom: 15px; border-bottom: 2px solid #1e88e5; padding-bottom: 8px;">
                    <i class="fas fa-university" style="margin-right: 8px;"></i>THÔNG TIN CHUYỂN KHOẢN
                </h6>
                <div style="margin-bottom: 10px;">
                    <strong>Ngân hàng:</strong> ${paymentData.account_info.bank_name}
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>Số tài khoản:</strong> 
                    <code style="background: #e9ecef; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 16px; margin-left: 5px;">
                        ${paymentData.account_info.account_number}
                    </code>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>Tên tài khoản:</strong> ${paymentData.account_info.account_name}
                </div>
                <div style="margin-bottom: 15px;">
                    <p><strong>Số tiền chuyển khoản:</strong> <span style="color: #2196F3; font-weight: bold;">${paymentData.amount_original_formatted}</span></p>
                    <p style="font-size: 13px; color: #666; margin: 5px 0;">
                        <i class="fas fa-info-circle"></i> QR code hiển thị số tiền đã chia theo quy tắc SePay
                    </p>
                    <p style="font-size: 13px; color: #666;">
                        Tổng tiền gốc: ${paymentData.amount_original_formatted}
                    </p>
                </div>
                
                <div style="margin-top: 15px;">
                    <strong>Nội dung chuyển khoản (QUAN TRỌNG):</strong>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-top: 10px; border: 2px dashed #ffc107;">
                        <code style="font-weight: bold; font-size: 16px; color: #856404; word-break: break-all; display: block; font-family: monospace;">
                            ${paymentData.transfer_content}
                        </code>
                        <div style="margin-top: 12px; color: #e65100; font-size: 14px; background: #fff8e1; padding: 10px; border-radius: 4px;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                            <strong>VUI LÒNG SAO CHÉP CHÍNH XÁC</strong> nội dung này khi chuyển khoản
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="important-notes" style="background: #e7f3ff; border: 2px solid #b8daff; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <div style="display: flex; align-items: start;">
                    <i class="fas fa-info-circle" style="color: #0d6efd; margin-top: 3px; margin-right: 12px; font-size: 20px;"></i>
                    <div style="text-align: left; flex: 1;">
                        <strong>Hướng dẫn thanh toán:</strong>
                        <ol style="margin: 8px 0 0 20px; padding-left: 0;">
                            <li style="margin-bottom: 6px;">Mở ứng dụng ngân hàng trên điện thoại</li>
                            <li style="margin-bottom: 6px;">Quét mã QR bên trên hoặc chuyển khoản thủ công</li>
                            <li style="margin-bottom: 6px;">Nhập <strong>chính xác</strong> nội dung chuyển khoản như trên</li>
                            <li style="margin-bottom: 6px;">Xác nhận chuyển khoản và đợi hệ thống tự động xác nhận</li>
                            <li>Không đóng trang này trong khi chờ xác nhận</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <div class="payment-status" style="text-align: center; padding: 20px; margin-top: 20px; border-top: 1px solid #dee2e6; background: #f8f9fa; border-radius: 8px;">
                
            </div>
        </div>
    `;
    
    qrSection.style.display = 'block';
    
    pollingCount = 1;
    const pollingCountElement = document.getElementById('pollingCount');
    if (pollingCountElement) {
        pollingCountElement.textContent = pollingCount;
    }
    
    // Bắt đầu đếm ngược 5 phút
    startCountdownTimer();
}

// ========== ĐẾM NGƯỢC 5 PHÚT ==========
function startCountdownTimer() {
    remainingTime = 300; // 5 phút = 300 giây
    updateCountdownDisplay();
    
    countdownInterval = setInterval(() => {
        remainingTime--;
        updateCountdownDisplay();
        
        // Đổi màu khi còn 1 phút
        if (remainingTime === 60) {
            const countdownElement = document.querySelector('.countdown-timer');
            if (countdownElement) {
                countdownElement.style.background = 'linear-gradient(135deg, #ff5722, #d32f2f)';
            }
        }
        
        // Đổi màu khi còn 30 giây
        if (remainingTime === 30) {
            const countdownElement = document.querySelector('.countdown-timer');
            if (countdownElement) {
                countdownElement.style.background = 'linear-gradient(135deg, #d32f2f, #b71c1c)';
                countdownElement.style.animation = 'pulse 1s infinite';
            }
        }
        
        if (remainingTime <= 0) {
            clearInterval(countdownInterval);
            showCountdownExpired();
        }
    }, 1000);
}

// ========== CẬP NHẬT HIỂN THỊ ĐẾM NGƯỢC ==========
function updateCountdownDisplay() {
    const minutes = Math.floor(remainingTime / 60);
    const seconds = remainingTime % 60;
    const display = document.getElementById('countdownDisplay');
    
    if (display) {
        display.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

// ========== HIỂN THỊ KHI HẾT THỜI GIAN ==========
function showCountdownExpired() {
    if (isPaymentCompleted) return; // Nếu đã thanh toán thành công thì không làm gì
    
    clearInterval(checkInterval);
    if (eventSource) {
        eventSource.close();
        eventSource = null;
    }
    
    const qrSection = document.getElementById('qrCodeSection');
    if (!qrSection) return;
    
    qrSection.innerHTML = `
        <div style="text-align: center; padding: 40px 20px;">
            <i class="fas fa-hourglass-end" style="font-size: 60px; color: #d32f2f; margin-bottom: 20px;"></i>
            <h4 style="color: #d32f2f; margin-bottom: 15px;">⏰ QR CODE ĐÃ HẾT HẠN</h4>
            
            <div style="background: #ffebee; border: 2px solid #ffcdd2; padding: 25px; border-radius: 10px; margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">
                <p style="margin-bottom: 15px; font-size: 16px; color: #b71c1c;">
                    <strong>QR code thanh toán đã hết hạn sau 5 phút.</strong>
                </p>
                <p style="margin-bottom: 15px; color: #555;">
                    Bạn đã không thực hiện thanh toán trong thời gian quy định. Mã QR này không còn hiệu lực.
                </p>
                <div style="background: #fff3e0; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #ff9800;">
                    <p style="margin: 0; font-size: 14px; color: #e65100;">
                        <i class="fas fa-info-circle"></i> <strong>Để tiếp tục thanh toán:</strong> Vui lòng đóng trang này và tạo yêu cầu thanh toán mới.
                    </p>
                </div>
            </div>
            
            <div style="margin-top: 30px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <button onclick="closePaymentModal()" class="btn-pay" 
                        style="padding: 14px 35px; font-size: 16px; background: linear-gradient(135deg, #d32f2f, #b71c1c); border: none; color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-times-circle" style="margin-right: 10px;"></i>ĐÓNG TRANG THANH TOÁN
                </button>
                
                <button onclick="location.reload()" class="btn-pay" 
                        style="padding: 14px 35px; font-size: 16px; background: linear-gradient(135deg, #2196F3, #0d47a1); border: none; color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-redo" style="margin-right: 10px;"></i>TẢI LẠI TRANG
                </button>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
                <p style="margin: 0; color: #666; font-size: 13px;">
                    <i class="fas fa-question-circle"></i> 
                    <strong>Lý do hết hạn:</strong> Mã QR chỉ có hiệu lực trong 5 phút để đảm bảo bảo mật giao dịch.
                </p>
            </div>
        </div>
    `;
    
    // Tự động đóng modal sau 10 giây
    setTimeout(() => {
        if (!isPaymentCompleted) {
            showGlobalNotification('⏰ QR code đã hết hạn. Vui lòng tạo yêu cầu thanh toán mới.', 'warning');
            closePaymentModal();
        }
    }, 10000);
    
    // Thêm animation
    if (!document.getElementById('pulse-style')) {
        const style = document.createElement('style');
        style.id = 'pulse-style';
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.02); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }
}

// ========== KIỂM TRA TRẠNG THÁI ==========
function startPaymentCheck(paymentCode, paymentGroupId, violationIds) {
    console.log('[Payment] Starting status check (Database polling)');
    
    const checkStatus = () => {
        if (isPaymentCompleted) {
            clearInterval(checkInterval);
            return;
        }
        
        pollingCount++;
        updatePollingCounter();
        
        fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${paymentGroupId}&t=${Date.now()}`)
            .then(response => response.json())
            .then(data => {
                console.log('[Payment] Database Status:', data);
                
                if (data.success && data.status === 'completed') {
                    isPaymentCompleted = true;
                    clearInterval(checkInterval);
                    clearInterval(countdownInterval);
                    
                    showPaymentSuccess(data);
                    showGlobalNotification('✅ Thanh toán đã được xác nhận thành công!', 'success');
                    
                    if (data.violation_ids && data.violation_ids.length > 0) {
                        updateViolationsListRealTime(data.violation_ids);
                    }
                    
                    updateRecentPaymentStatus(paymentGroupId, 'completed');
                    
                    setTimeout(() => {
                        const shouldReload = confirm('🎉 Thanh toán thành công! Bạn có muốn tải lại trang để xem cập nhật mới nhất?');
                        if (shouldReload) {
                            location.reload();
                        }
                    }, 8000);
                    
                } else if (data.success && data.status === 'pending') {
                    updatePollingMessage(`Đang kiểm tra thanh toán...`);
                }
            })
            .catch(error => {
                console.error('[Payment] Database check error:', error);
            });
    };
    
    checkStatus();
    checkInterval = setInterval(checkStatus, 3000);
}

// ========== CẬP NHẬT BỘ ĐẾM ==========
function updatePollingCounter() {
    const pollingCountElement = document.getElementById('pollingCount');
    if (pollingCountElement) {
        pollingCountElement.textContent = pollingCount;
    }
}

function updatePollingMessage(message) {
    const statusElement = document.querySelector('.payment-status p');
    if (statusElement) {
        statusElement.innerHTML = `<i class="fas fa-sync fa-spin"></i> ${message}`;
    }
}

// ========== HIỂN THỊ THÀNH CÔNG ==========
function showPaymentSuccess(data) {
    const qrSection = document.getElementById('qrCodeSection');
    if (qrSection) {
        qrSection.style.display = 'none';
    }
    
    const successSection = document.getElementById('paymentSuccess');
    if (!successSection) {
        console.error('[Payment] Success section not found');
        return;
    }
    
    const paymentTime = data.transaction_info?.payment_time || new Date().toLocaleString('vi-VN');
    
    successSection.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="animation: celebrate 1s ease-out;">
                <i class="fas fa-check-circle" style="font-size: 70px; color: #28a745; margin-bottom: 20px; animation: bounce 0.5s ease-in-out 3;"></i>
            </div>
            <h4 class="text-success" style="margin-bottom: 15px; font-size: 24px;">🎉 THANH TOÁN THÀNH CÔNG!</h4>
            <p style="margin-bottom: 25px; font-size: 16px; color: #495057;">
                Vi phạm đã được thanh toán thành công. Cảm ơn bạn đã sử dụng dịch vụ!
            </p>
            
            <div style="background: linear-gradient(135deg, #d4edda, #c3e6cb); padding: 25px; border-radius: 12px; margin: 0 auto 25px; max-width: 500px; text-align: left; border: 2px solid #28a745; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);">
                <h5 style="border-bottom: 2px solid #28a745; padding-bottom: 12px; margin-bottom: 20px; color: #155724; display: flex; align-items: center;">
                    <i class="fas fa-receipt" style="margin-right: 12px;"></i>BIÊN LAI ĐIỆN TỬ
                </h5>
                
                <div style="margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-hashtag" style="margin-right: 8px; color: #6c757d;"></i> Mã giao dịch:
                        </span>
                        <span style="font-weight: 700; color: #155724; font-family: monospace;">${data.transaction_info?.transaction_id || 'MANUAL_UPDATE'}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-car" style="margin-right: 8px; color: #6c757d;"></i> Biển số xe:
                        </span>
                        <span style="font-weight: 700;">${currentLicensePlate}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-list-ol" style="margin-right: 8px; color: #6c757d;"></i> Số vi phạm:
                        </span>
                        <span style="font-weight: 700;">${currentViolationIds.length} vi phạm</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-money-bill-wave" style="margin-right: 8px; color: #6c757d;"></i> Tổng tiền:
                        </span>
                        <span style="color: #dc3545; font-weight: 800; font-size: 18px;">
                            ${formatCurrency(currentTotalAmount)}
                        </span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 5px 0;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-clock" style="margin-right: 8px; color: #6c757d;"></i> Thời gian:
                        </span>
                        <span style="font-weight: 700;">${paymentTime}</span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px dashed #dee2e6;">
                        <span style="color: #495057; display: flex; align-items: center;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: #6c757d;"></i> Trạng thái:
                        </span>
                        <span style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 700; box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);">
                            <i class="fas fa-check"></i> ĐÃ THANH TOÁN
                        </span>
                    </div>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <h6 style="color: #856404; margin-bottom: 10px; display: flex; align-items: center;">
                        <i class="fas fa-lightbulb" style="margin-right: 8px;"></i> Thông tin quan trọng:
                    </h6>
                    <ul style="margin: 0; padding-left: 20px; color: #856404;">
                        <li style="margin-bottom: 5px;">Vi phạm đã được cập nhật trạng thái <strong>"Đã thanh toán"</strong></li>
                        <li style="margin-bottom: 5px;">Bạn có thể tra cứu lại để xác nhận</li>
                        <li>Lưu giữ biên lai này để đối chiếu khi cần</li>
                    </ul>
                </div>
            </div>
            
            <div style="margin-top: 25px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                <button onclick="location.reload()" class="btn-pay btn-pay-confirm" 
                        style="padding: 14px 35px; font-size: 16px; background: linear-gradient(135deg, #28a745, #20c997); border: none; color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-redo" style="margin-right: 10px;"></i>TẢI LẠI TRANG
                </button>
                
                <button onclick="printReceipt()" class="btn-pay" 
                        style="padding: 14px 35px; font-size: 16px; background: linear-gradient(135deg, #17a2b8, #138496); border: none; color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-print" style="margin-right: 10px;"></i>IN BIÊN LAI
                </button>
                
                <button onclick="closePaymentModal()" class="btn-pay btn-pay-cancel" 
                        style="padding: 14px 35px; font-size: 16px; background: linear-gradient(135deg, #6c757d, #5a6268); border: none; color: white; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                    <i class="fas fa-times" style="margin-right: 10px;"></i>ĐÓNG
                </button>
            </div>
            
            <div style="margin-top: 25px; padding: 15px; background: #e7f3ff; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px; color: #0d6efd; margin-bottom: 10px;">
                    <i class="fas fa-sync-alt fa-spin"></i>
                    <span style="font-weight: 600;">Danh sách vi phạm đang được cập nhật tự động...</span>
                </div>
                <div style="margin-top: 10px; font-size: 14px; color: #495057; text-align: center;">
                    <div style="background: white; padding: 10px; border-radius: 6px; margin-bottom: 10px;">
                        <i class="fas fa-check text-success" style="margin-right: 5px;"></i>
                        Trạng thái các vi phạm đã được cập nhật thành công
                    </div>
                    Trang sẽ tự động hiển thị trạng thái mới trong vài giây.
                    <br>Nếu không thấy cập nhật, vui lòng nhấn "Tải lại trang".
                </div>
            </div>
        </div>
        
        <style>
            @keyframes celebrate {
                0% { transform: scale(0.8); opacity: 0; }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
        </style>
    `;
    
    successSection.style.display = 'block';
    
    updateViolationsListRealTime(data.violation_ids || currentViolationIds);
    
    setTimeout(() => {
        const cancelBtn = document.querySelector('.btn-pay-cancel');
        if (cancelBtn && cancelBtn.style.display !== 'none') {
            showGlobalNotification('Modal đã tự động đóng. Trạng thái vi phạm đã được cập nhật!', 'info');
            closePaymentModal();
        }
    }, 60000);
}

// ========== TIMEOUT ==========
function showPaymentTimeout() {
    const qrSection = document.getElementById('qrCodeSection');
    if (!qrSection) return;
    
    qrSection.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <i class="fas fa-clock" style="font-size: 48px; color: #ff9800; margin-bottom: 15px;"></i>
            <h5 style="color: #ff9800; margin-bottom: 15px;">CHƯA NHẬN ĐƯỢC THANH TOÁN</h5>
            
            <div style="background: #fff3e0; border: 2px solid #ffcc80; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <p style="margin-bottom: 12px; font-weight: 600;">Hệ thống chưa nhận được xác nhận thanh toán.</p>
                <p style="margin: 0 0 10px 0;">Nếu bạn đã chuyển khoản, có thể do:</p>
                <ul style="margin: 10px 0 0 20px; padding-left: 0;">
                    <li style="margin-bottom: 5px;">Ngân hàng xử lý chậm</li>
                    <li style="margin-bottom: 5px;">Nội dung chuyển khoản không chính xác</li>
                    <li style="margin-bottom: 5px;">Hệ thống đang bảo trì</li>
                    <li style="margin-bottom: 5px;">Bạn có thể dùng <strong>Manual Webhook</strong> để cập nhật thủ công</li>
                </ul>
            </div>
            
            <div style="margin-top: 25px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <button onclick="location.reload()" class="btn-pay btn-pay-confirm" 
                        style="padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-redo" style="margin-right: 8px;"></i>TẢI LẠI TRANG
                </button>
                
                <button onclick="openManualWebhook()" class="btn-pay" 
                        style="padding: 12px 25px; background: #6f42c1; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-tools" style="margin-right: 8px;"></i>MANUAL WEBHOOK
                </button>
                
                <button onclick="closePaymentModal()" class="btn-pay btn-pay-cancel"
                        style="padding: 12px 25px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    <i class="fas fa-times" style="margin-right: 8px;"></i>ĐÓNG
                </button>
            </div>
            
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee;">
                <small class="text-muted">
                    <i class="fas fa-headset" style="margin-right: 5px;"></i>
                    Cần hỗ trợ? Liên hệ: 1900 0000
                </small>
            </div>
        </div>
    `;
}

// ========== MỞ MANUAL WEBHOOK ==========
function openManualWebhook() {
    closePaymentModal();
    setTimeout(() => {
        const url = '/traffic/app/controllers/manual_webhook.php';
        if (currentPaymentCode) {
            window.open(url + '?code=' + encodeURIComponent(currentPaymentCode), '_blank');
        } else {
            window.open(url, '_blank');
        }
    }, 300);
}

// ========== HIỂN THỊ THÔNG BÁO TOÀN TRANG ==========
function showGlobalNotification(message, type = 'info') {
    const oldNotification = document.getElementById('global-notification');
    if (oldNotification) {
        oldNotification.remove();
    }
    
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'info': '#17a2b8',
        'warning': '#ffc107'
    };
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'info': 'fa-info-circle',
        'warning': 'fa-exclamation-triangle'
    };
    
    const notification = document.createElement('div');
    notification.id = 'global-notification';
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
        min-width: 300px;
        border-left: 4px solid ${colors[type] ? colors[type] + 'CC' : colors.info + 'CC'};
    `;
    
    notification.innerHTML = `
        <i class="fas ${icons[type] || 'fa-info-circle'}" 
           style="font-size: 22px; margin-right: 15px; flex-shrink: 0;"></i>
        <div style="flex: 1;">
            <div style="font-weight: 600; margin-bottom: 3px;">${message}</div>
            <div style="font-size: 11px; opacity: 0.9;">
                ${new Date().toLocaleTimeString('vi-VN')}
            </div>
        </div>
        <button onclick="this.parentElement.remove()" 
                style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px; font-size: 16px;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, 8000);
}

// ========== CẬP NHẬT DANH SÁCH VI PHẠM REAL-TIME ==========
function updateViolationsListRealTime(violationIds) {
    violationIds.forEach(violationId => {
        const violationRow = document.querySelector(`tr[data-violation-id="${violationId}"]`);
        if (violationRow) {
            const statusCell = violationRow.querySelector('.violation-status');
            if (statusCell) {
                statusCell.innerHTML = `
                    <span class="badge badge-success" style="animation: fadeIn 0.5s;">
                        <i class="fas fa-check-circle"></i> Đã thanh toán
                    </span>
                `;
            }
            
            const payButton = violationRow.querySelector('.btn-pay-single');
            if (payButton) {
                payButton.disabled = true;
                payButton.innerHTML = '<i class="fas fa-check"></i> Đã thanh toán';
                payButton.style.opacity = '0.6';
                payButton.style.cursor = 'not-allowed';
                payButton.classList.remove('btn-pay-single');
            }
            
            violationRow.style.backgroundColor = '#f8fff9';
            violationRow.style.transition = 'background-color 0.5s';
            
            setTimeout(() => {
                violationRow.style.backgroundColor = '';
            }, 3000);
        }
    });
    
    updateTotalAmountDisplay();
    
    if (!document.getElementById('fadeIn-style')) {
        const style = document.createElement('style');
        style.id = 'fadeIn-style';
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-5px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);
    }
}

// ========== CẬP NHẬT HIỂN THỊ TỔNG TIỀN ==========
function updateTotalAmountDisplay() {
    const unpaidViolations = document.querySelectorAll('.violation-status .badge-warning');
    const totalAmountElement = document.querySelector('.total-amount-display');
    const btnPayAll = document.querySelector('.btn-pay-all');
    
    if (unpaidViolations.length === 0 && totalAmountElement) {
        totalAmountElement.innerHTML = `
            <div style="color: #28a745; font-weight: bold; padding: 10px; background: #f8fff9; border-radius: 6px; border: 1px solid #d4edda;">
                <i class="fas fa-check-circle"></i> Tất cả vi phạm đã được thanh toán
            </div>
        `;
    }
    
    if (btnPayAll && unpaidViolations.length === 0) {
        btnPayAll.disabled = true;
        btnPayAll.innerHTML = '<i class="fas fa-check"></i> Đã thanh toán tất cả';
        btnPayAll.style.opacity = '0.6';
        btnPayAll.style.cursor = 'not-allowed';
        btnPayAll.classList.remove('btn-pay-all');
    }
}

// ========== KIỂM TRA URL ==========
function checkUrlForPendingPayment() {
    const urlParams = new URLSearchParams(window.location.search);
    const pendingPaymentId = urlParams.get('pending_payment');
    
    if (pendingPaymentId) {
        console.log('[Payment] Found pending payment in URL:', pendingPaymentId);
        showGlobalNotification('🔍 Đang kiểm tra trạng thái thanh toán trước đó...', 'info');
        
        setTimeout(() => {
            checkExistingPayment(pendingPaymentId);
        }, 1500);
    }
}

function checkExistingPayment(paymentGroupId) {
    fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${paymentGroupId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status === 'completed') {
                showGlobalNotification('✅ Thanh toán trước đó đã thành công!', 'success');
                
                if (data.violation_ids && data.violation_ids.length > 0) {
                    updateViolationsListRealTime(data.violation_ids);
                }
                
                setTimeout(() => {
                    const searchForm = document.getElementById('tracuu');
                    if (searchForm) {
                        searchForm.submit();
                    }
                }, 3000);
            }
        })
        .catch(error => {
            console.error('[Payment] Check existing error:', error);
        });
}

// ========== IN BIÊN LAI ==========
function printReceipt() {
    const receiptContent = `
        <div style="padding: 25px; font-family: 'Arial', sans-serif; max-width: 400px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #28a745; margin: 0; padding-bottom: 10px; border-bottom: 2px solid #28a745;">
                    BIÊN LAI THANH TOÁN VI PHẠM
                </h2>
                <p style="color: #6c757d; margin: 5px 0;">Hệ thống quản lý vi phạm giao thông</p>
            </div>
            
            <div style="margin: 25px 0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px dashed #dee2e6;">
                    <span style="color: #495057;"><strong>Mã giao dịch:</strong></span>
                    <span style="font-weight: 700; color: #155724;">${currentPaymentCode}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px dashed #dee2e6;">
                    <span style="color: #495057;"><strong>Biển số xe:</strong></span>
                    <span style="font-weight: 700;">${currentLicensePlate}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px dashed #dee2e6;">
                    <span style="color: #495057;"><strong>Số vi phạm:</strong></span>
                    <span style="font-weight: 700;">${currentViolationIds.length} vi phạm</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px dashed #dee2e6;">
                    <span style="color: #495057;"><strong>Tổng tiền:</strong></span>
                    <span style="color: #dc3545; font-weight: 800; font-size: 18px;">
                        ${formatCurrency(currentTotalAmount)}
                    </span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px dashed #dee2e6;">
                    <span style="color: #495057;"><strong>Thời gian:</strong></span>
                    <span style="font-weight: 700;">${new Date().toLocaleString('vi-VN')}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px;">
                    <span style="color: #495057;"><strong>Trạng thái:</strong></span>
                    <span style="background: #28a745; color: white; padding: 5px 15px; border-radius: 15px; font-weight: 700;">
                        ĐÃ THANH TOÁN
                    </span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px dashed #ccc;">
                <p style="color: #6c757d; font-size: 12px; margin: 5px 0;">Cảm ơn bạn đã sử dụng dịch vụ!</p>
                <p style="color: #6c757d; font-size: 11px; margin: 5px 0;">Vui lòng giữ biên lai này để đối chiếu khi cần</p>
            </div>
        </div>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Biên lai thanh toán - ${currentLicensePlate}</title>
                <style>
                    @media print {
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            font-family: Arial, sans-serif;
                        }
                        @page { 
                            margin: 15mm; 
                            size: A5;
                        }
                    }
                    body { 
                        font-family: Arial, sans-serif;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                </style>
            </head>
            <body>${receiptContent}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

// ========== FORMAT TIỀN ==========
function formatCurrency(amount) {
    if (!amount && amount !== 0) return '0 VND';
    return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") + ' VND';
}

// ========== KIỂM TRA KHI TRANG LOAD LẠI ==========
window.addEventListener('load', function() {
    const lastPaymentId = sessionStorage.getItem('last_payment_id');
    const lastPaymentTime = parseInt(sessionStorage.getItem('last_payment_time') || '0');
    const tenMinutesAgo = Date.now() - (10 * 60 * 1000);
    
    if (lastPaymentId && lastPaymentTime > tenMinutesAgo) {
        fetch(`/traffic/app/controllers/PaymentController.php?action=check_db&payment_id=${lastPaymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'completed') {
                    showGlobalNotification('✅ Thanh toán trước đó đã thành công!', 'success');
                }
            });
    }
});

console.log('[Payment] Real-time payment system with 5-minute countdown loaded successfully');
</script>

<!-- ==================== FOOTER ==================== -->
<?php 
// Include chatbot if exists
$chatbotFile = __DIR__ . '/chatbot_ui.php';
if (file_exists($chatbotFile)) {
    include $chatbotFile;
}

// Include footer
include __DIR__ . '/../violations/violations_footer.php';
?>