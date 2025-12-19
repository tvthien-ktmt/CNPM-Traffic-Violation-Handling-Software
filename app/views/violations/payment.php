<?php
/**
 * Payment Page - Trang thanh toán
 * File: app/views/violations/payment.php
 */

require_once __DIR__ . '/../../models/Violation.php';
require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../services/SePayService.php';

// Khởi tạo models
$violationModel = new Violation();
$paymentModel = new Payment();

// Lấy thông tin từ URL
$bienSo = $_GET['bien_so'] ?? '';
$violationId = $_GET['violation_id'] ?? '';
$isMultiple = isset($_GET['multiple']) && $_GET['multiple'] === 'true';

// Redirect nếu thiếu thông tin
if (empty($bienSo)) {
    header('Location: /traffic/app/views/violations/search.php');
    exit;
}

// Chuẩn hóa biển số
$bienSo = strtoupper(str_replace(['-', '.', ' '], '', $bienSo));

// Lấy danh sách vi phạm
$violations = [];
$tongTien = 0;

if ($isMultiple) {
    // Thanh toán nhiều vi phạm - lấy tất cả vi phạm chưa xử lý
    $allViolations = $violationModel->getViolationsByLicensePlate($bienSo);
    
    foreach ($allViolations as $v) {
        if ($v['trang_thai'] === 'Chưa xử lý') {
            $violations[] = $v;
            $tongTien += $v['muc_phat'];
        }
    }
} elseif ($violationId) {
    // Thanh toán 1 vi phạm cụ thể
    $violation = $violationModel->getViolationById($violationId);
    if ($violation && $violation['trang_thai'] === 'Chưa xử lý') {
        $violations[] = $violation;
        $tongTien = $violation['muc_phat'];
    }
}

// Kiểm tra có vi phạm không
if (empty($violations)) {
    header('Location: /traffic/app/views/violations/search.php?bien_so=' . urlencode($bienSo) . '&error=no_violations');
    exit;
}

include __DIR__ . '/violations_header.php';
?>

<style>
.payment-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.payment-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 20px;
}

.violation-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    background: #f8f9fa;
}

.violation-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.violation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #dee2e6;
}

.violation-type {
    font-size: 18px;
    font-weight: bold;
    color: #dc3545;
}

.violation-amount {
    font-size: 24px;
    font-weight: bold;
    color: #dc3545;
}

.violation-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}

.info-value {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.total-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    margin: 30px 0;
}

.total-label {
    font-size: 16px;
    margin-bottom: 10px;
}

.total-amount {
    font-size: 36px;
    font-weight: bold;
}

.qr-section {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
    margin: 20px 0;
}

#qrCodeImage {
    width: 300px;
    height: 300px;
    margin: 20px auto;
    border: 3px solid #007bff;
    border-radius: 8px;
    padding: 10px;
    background: white;
}

.payment-instructions {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.payment-instructions h4 {
    color: #856404;
    margin-bottom: 15px;
}

.payment-instructions ol {
    margin: 0;
    padding-left: 20px;
}

.payment-instructions li {
    margin-bottom: 10px;
    color: #856404;
}

.btn-create-payment {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    margin: 20px 0;
    transition: all 0.3s;
}

.btn-create-payment:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-create-payment:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.status-message {
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: center;
    font-weight: 500;
}

.status-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-pending {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.bank-info {
    background: white;
    border: 2px solid #007bff;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.bank-info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.bank-info-item:last-child {
    border-bottom: none;
}

.bank-info-label {
    font-weight: 500;
    color: #666;
}

.bank-info-value {
    font-weight: bold;
    color: #007bff;
}

.loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="payment-container">
    <div class="payment-card">
        <h2 style="color: #004aad; margin-bottom: 30px;">
            <i class="fas fa-credit-card"></i> Thanh Toán Phạt Vi Phạm
        </h2>

        <!-- Thông tin chủ xe -->
        <?php if (!empty($violations[0]['ho_ten'])): ?>
        <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Chủ xe:</strong> <?= htmlspecialchars($violations[0]['ho_ten']) ?> |
            <strong>CCCD:</strong> <?= htmlspecialchars($violations[0]['cccd']) ?> |
            <strong>SĐT:</strong> <?= htmlspecialchars($violations[0]['so_dien_thoai']) ?>
        </div>
        <?php endif; ?>

        <!-- Danh sách vi phạm -->
        <h3 style="margin-bottom: 20px;">
            <?= $isMultiple ? 'Danh sách vi phạm cần thanh toán:' : 'Vi phạm cần thanh toán:' ?>
        </h3>
        
        <?php foreach ($violations as $index => $violation): ?>
        <div class="violation-item">
            <div class="violation-header">
                <div>
                    <div class="violation-type">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($violation['ten_loi']) ?>
                    </div>
                    <small style="color: #666;">Mã vi phạm: <?= htmlspecialchars($violation['ma_vi_pham']) ?></small>
                </div>
                <div class="violation-amount">
                    <?= number_format($violation['muc_phat'], 0, ',', '.') ?> đ
                </div>
            </div>
            
            <div class="violation-info">
                <div class="info-item">
                    <span class="info-label">Biển số</span>
                    <span class="info-value"><?= htmlspecialchars($violation['bien_so']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Thời gian</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($violation['thoi_gian_vi_pham'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Địa điểm</span>
                    <span class="info-value"><?= htmlspecialchars($violation['dia_diem']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Loại xe</span>
                    <span class="info-value"><?= htmlspecialchars($violation['loai_xe']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Tổng tiền -->
        <div class="total-section">
            <div class="total-label">Tổng số tiền phải thanh toán</div>
            <div class="total-amount"><?= number_format($tongTien, 0, ',', '.') ?> đ</div>
            <small style="opacity: 0.9;">
                (<?= count($violations) ?> vi phạm)
            </small>
        </div>

        <!-- Nút tạo thanh toán -->
        <button id="btnCreatePayment" class="btn-create-payment" onclick="createPayment()">
            <i class="fas fa-qrcode"></i> Tạo Mã QR Thanh Toán
        </button>

        <!-- QR Code -->
        <div id="qrSection" class="qr-section" style="display: none;">
            <h3 style="color: #007bff;">Quét mã QR để thanh toán</h3>
            <img id="qrCodeImage" src="" alt="QR Code" />
            
            <!-- Thông tin ngân hàng -->
            <div class="bank-info">
                <h4 style="text-align: center; color: #007bff; margin-bottom: 15px;">
                    Hoặc chuyển khoản thủ công
                </h4>
                <div class="bank-info-item">
                    <span class="bank-info-label">Ngân hàng:</span>
                    <span class="bank-info-value" id="bankName">MB Bank</span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label">Số tài khoản:</span>
                    <span class="bank-info-value" id="accountNumber">-</span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label">Tên tài khoản:</span>
                    <span class="bank-info-value" id="accountName">-</span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label">Số tiền:</span>
                    <span class="bank-info-value" style="color: #dc3545; font-size: 20px;">
                        <?= number_format($tongTien, 0, ',', '.') ?> đ
                    </span>
                </div>
                <div class="bank-info-item">
                    <span class="bank-info-label">Nội dung:</span>
                    <span class="bank-info-value" id="transferContent">-</span>
                </div>
            </div>

            <!-- Loading -->
            <div id="checkingStatus" style="display: none;">
                <div class="loading-spinner"></div>
                <p style="text-align: center; color: #666;">Đang kiểm tra thanh toán...</p>
            </div>
        </div>

        <!-- Thông báo trạng thái -->
        <div id="statusMessage"></div>

        <!-- Hướng dẫn thanh toán -->
        <div class="payment-instructions">
            <h4><i class="fas fa-info-circle"></i> Hướng dẫn thanh toán</h4>
            <ol>
                <li>Nhấn nút "Tạo Mã QR Thanh Toán" để tạo mã QR</li>
                <li>Mở ứng dụng ngân hàng trên điện thoại</li>
                <li>Quét mã QR hoặc chuyển khoản thủ công theo thông tin bên trên</li>
                <li><strong>Lưu ý:</strong> Nhập đúng nội dung chuyển khoản để hệ thống tự động xác nhận</li>
                <li>Sau khi chuyển khoản thành công, hệ thống sẽ tự động kiểm tra và xuất biên lai</li>
            </ol>
        </div>
    </div>
</div>

<script>
let checkInterval = null;
let transactionCode = null;
const violations = <?= json_encode(array_column($violations, 'id')) ?>;
const totalAmount = <?= $tongTien ?>;
const bienSo = '<?= htmlspecialchars($bienSo) ?>';

async function createPayment() {
    const btn = document.getElementById('btnCreatePayment');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo mã thanh toán...';

    try {
        const response = await fetch('/traffic/app/controllers/PaymentController.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                violations: violations,
                total_amount: totalAmount,
                bien_so: bienSo
            })
        });

        const result = await response.json();

        if (result.success) {
            transactionCode = result.transaction_code;
            
            // Hiển thị QR code
            document.getElementById('qrCodeImage').src = result.qr_code_url;
            document.getElementById('accountNumber').textContent = result.account_number;
            document.getElementById('accountName').textContent = result.account_name;
            document.getElementById('transferContent').textContent = result.content;
            document.getElementById('qrSection').style.display = 'block';
            
            // Ẩn nút
            btn.style.display = 'none';
            
            showStatus('Đã tạo mã QR thành công. Vui lòng quét mã để thanh toán.', 'pending');
            
            // Bắt đầu check thanh toán
            startCheckingPayment();
            
        } else {
            showStatus('Lỗi: ' + (result.error || 'Không thể tạo thanh toán'), 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-qrcode"></i> Tạo Mã QR Thanh Toán';
        }
    } catch (error) {
        console.error('Error:', error);
        showStatus('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-qrcode"></i> Tạo Mã QR Thanh Toán';
    }
}

function startCheckingPayment() {
    document.getElementById('checkingStatus').style.display = 'block';
    
    checkInterval = setInterval(async () => {
        try {
            const response = await fetch(
                `/traffic/app/controllers/PaymentController.php?action=check&code=${transactionCode}`
            );
            const result = await response.json();

            if (result.status === 'success') {
                clearInterval(checkInterval);
                document.getElementById('checkingStatus').style.display = 'none';
                showStatus('✅ Thanh toán thành công!', 'success');
                
                // Chuyển đến trang thành công sau 2 giây
                setTimeout(() => {
                    window.location.href = `/traffic/app/views/violations/payment_success.php?code=${transactionCode}`;
                }, 2000);
            }
        } catch (error) {
            console.error('Check error:', error);
        }
    }, 5000); // Check mỗi 5 giây
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('statusMessage');
    statusDiv.textContent = message;
    statusDiv.className = 'status-message status-' + type;
    statusDiv.style.display = 'block';
}

// Dừng check khi rời trang
window.addEventListener('beforeunload', () => {
    if (checkInterval) {
        clearInterval(checkInterval);
    }
});
</script>

<?php include __DIR__ . '/violations_footer.php'; ?>