<?php
/**
 * Payment History Page
 * File: app/views/violations/history.php
 * 
 * Xem lịch sử thanh toán theo biển số
 */

require_once __DIR__ . '/../../models/Payment.php';

$bienSo = $_GET['bien_so'] ?? $_POST['bien_so'] ?? '';
$payments = [];

if (!empty($bienSo)) {
    $paymentModel = new Payment();
    $payments = $paymentModel->getPaymentHistoryByLicensePlate($bienSo);
}

include __DIR__ . '/violations_header.php';
?>

<style>
.history-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.search-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
}

.search-input-group {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-input {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
}

.search-input:focus {
    outline: none;
    border-color: #007bff;
}

.btn-search {
    padding: 15px 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.history-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.history-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 30px;
}

.history-header h2 {
    margin: 0;
    font-size: 24px;
}

.history-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 30px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.payment-list {
    padding: 20px;
}

.payment-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.payment-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateX(5px);
}

.payment-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.payment-code {
    font-weight: bold;
    color: #667eea;
    font-size: 16px;
}

.payment-amount {
    font-size: 24px;
    font-weight: bold;
    color: #11998e;
}

.payment-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.detail-icon {
    color: #667eea;
    width: 20px;
}

.detail-text {
    color: #666;
}

.detail-text strong {
    color: #333;
}

.payment-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f8f9fa;
    text-align: right;
}

.btn-download-receipt {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-download-receipt:hover {
    background: #0056b3;
    color: white;
}

.no-history {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-history i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
}
</style>

<div class="history-container">
    <!-- Form tìm kiếm -->
    <div class="search-box">
        <h2 style="color: #004aad; margin-bottom: 20px;">
            <i class="fas fa-history"></i> Tra Cứu Lịch Sử Thanh Toán
        </h2>
        
        <form method="GET" action="">
            <div class="search-input-group">
                <input 
                    type="text" 
                    name="bien_so" 
                    class="search-input" 
                    placeholder="Nhập biển số xe (VD: 29A12345)"
                    value="<?= htmlspecialchars($bienSo) ?>"
                    required
                    onkeyup="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-\.]/g, '')"
                />
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Tra Cứu
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($bienSo)): ?>
        <?php if (!empty($payments)): ?>
            <!-- Kết quả -->
            <div class="history-card">
                <!-- Header -->
                <div class="history-header">
                    <h2>Lịch sử thanh toán: <?= htmlspecialchars($bienSo) ?></h2>
                    <p style="margin: 5px 0 0 0; opacity: 0.9;">
                        Tổng cộng <?= count($payments) ?> giao dịch
                    </p>
                </div>

                <!-- Thống kê -->
                <div class="history-stats">
                    <div class="stat-item">
                        <div class="stat-label">Tổng giao dịch</div>
                        <div class="stat-value"><?= count($payments) ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Tổng tiền đã đóng</div>
                        <div class="stat-value" style="color: #11998e;">
                            <?= number_format(array_sum(array_column($payments, 'so_tien')), 0, ',', '.') ?> đ
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Chủ xe</div>
                        <div class="stat-value" style="font-size: 16px;">
                            <?= htmlspecialchars($payments[0]['ho_ten'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>

                <!-- Danh sách thanh toán -->
                <div class="payment-list">
                    <?php foreach ($payments as $index => $payment): ?>
                    <div class="payment-item">
                        <div class="payment-header-row">
                            <div>
                                <div class="payment-code">
                                    <i class="fas fa-receipt"></i>
                                    <?= htmlspecialchars($payment['ma_thanh_toan']) ?>
                                </div>
                                <small style="color: #666;">
                                    Giao dịch #<?= $index + 1 ?>
                                </small>
                            </div>
                            <div class="payment-amount">
                                <?= number_format($payment['so_tien'], 0, ',', '.') ?> đ
                            </div>
                        </div>

                        <div class="payment-details">
                            <div class="detail-item">
                                <i class="fas fa-exclamation-triangle detail-icon"></i>
                                <span class="detail-text">
                                    <strong>Lỗi:</strong> <?= htmlspecialchars($payment['ten_loi'] ?? 'N/A') ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-clock detail-icon"></i>
                                <span class="detail-text">
                                    <strong>Vi phạm:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($payment['thoi_gian_vi_pham'] ?? '')) ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-credit-card detail-icon"></i>
                                <span class="detail-text">
                                    <strong>Thanh toán:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($payment['thoi_gian_thanh_toan'])) ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-check-circle detail-icon" style="color: #11998e;"></i>
                                <span class="detail-text" style="color: #11998e;">
                                    <strong>Đã thanh toán</strong>
                                </span>
                            </div>
                        </div>

                        <div class="payment-actions">
                            <a href="/traffic/app/views/violations/export_receipt.php?code=<?= urlencode($payment['ma_thanh_toan']) ?>" 
                               class="btn-download-receipt" target="_blank">
                                <i class="fas fa-download"></i>
                                Tải biên lai
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Không có lịch sử -->
            <div class="history-card">
                <div class="no-history">
                    <i class="fas fa-inbox"></i>
                    <h3>Không tìm thấy lịch sử thanh toán</h3>
                    <p>Biển số <strong><?= htmlspecialchars($bienSo) ?></strong> chưa có giao dịch thanh toán nào.</p>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Chưa tìm kiếm -->
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Nhập biển số xe để tra cứu lịch sử thanh toán</h3>
            <p>Vui lòng nhập biển số xe vào ô tìm kiếm phía trên</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/violations_footer.php'; ?>