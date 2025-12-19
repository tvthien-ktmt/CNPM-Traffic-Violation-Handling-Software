<?php
// manual_webhook.php - FIXED VERSION
// Đặt trong thư mục controllers

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Violation.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>🔧 Manual Webhook Simulator</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .form-group { margin: 20px 0; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        input[type='text'] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #28a745; color: white; border: none; padding: 14px 28px; font-size: 16px; cursor: pointer; border-radius: 5px; transition: background 0.3s; }
        button:hover { background: #218838; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #ffeaa7; }
        .payment-list { border: 1px solid #ddd; border-radius: 5px; margin: 20px 0; }
        .payment-item { padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s; }
        .payment-item:hover { background: #f8f9fa; }
        .payment-item:last-child { border-bottom: none; }
        .payment-code { font-family: monospace; color: #007bff; }
        .timestamp { font-size: 12px; color: #6c757d; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #dee2e6; overflow: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>🔧 Manual Webhook Simulator</h2>";
        
try {
    // Khởi tạo database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Khởi tạo models
    $paymentModel = new Payment();
    $violationModel = new Violation();
    
    // Xử lý form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $paymentCode = trim($_POST['payment_code'] ?? '');
        
        if (empty($paymentCode)) {
            echo "<div class='error'>❌ Vui lòng nhập Payment Code</div>";
        } else {
            // Tìm payment bằng code
            $payment = $paymentModel->getPaymentByContent($paymentCode);
            
            if ($payment) {
                echo "<div class='success'>✅ Tìm thấy payment!</div>";
                echo "<div class='warning'>
                    <strong>Payment Group ID:</strong> " . htmlspecialchars($payment['payment_group_id']) . "<br>
                    <strong>Nội dung:</strong> " . htmlspecialchars($payment['noi_dung_chuyen_khoan']) . "<br>
                    <strong>Trạng thái hiện tại:</strong> " . htmlspecialchars($payment['trang_thai']) . "
                </div>";
                
                // Cập nhật payments
                $updateData = [
                    'trang_thai' => 'Thành công',
                    'sepay_transaction_id' => 'MANUAL_' . time(),
                    'reference_number' => $paymentCode,
                    'thoi_gian_xac_nhan' => date('Y-m-d H:i:s'),
                    'thoi_gian_thanh_toan' => date('Y-m-d H:i:s')
                ];
                
                $updated = $paymentModel->updatePaymentByGroupId($payment['payment_group_id'], $updateData);
                
                if ($updated) {
                    echo "<div class='success'>
                        <h3>✅ CẬP NHẬT PAYMENTS THÀNH CÔNG!</h3>
                        <p><strong>Payment Group:</strong> " . htmlspecialchars($payment['payment_group_id']) . "</p>
                        <p><strong>Thời gian:</strong> " . date('Y-m-d H:i:s') . "</p>
                    </div>";
                    
                    // Cập nhật violations
                    try {
                        $violationIds = $paymentModel->getViolationIdsByGroupId($payment['payment_group_id']);
                        
                        if (!empty($violationIds)) {
                            $updatedViolations = 0;
                            $failedViolations = [];
                            
                            foreach ($violationIds as $violationId) {
                                if ($violationModel->updateViolationStatus($violationId, 'Đã thanh toán')) {
                                    $updatedViolations++;
                                } else {
                                    $failedViolations[] = $violationId;
                                }
                            }
                            
                            echo "<div class='success'>✅ Đã cập nhật $updatedViolations violations</div>";
                            
                            if (!empty($failedViolations)) {
                                echo "<div class='warning'>⚠ Không thể cập nhật các violations: " . implode(', ', $failedViolations) . "</div>";
                            }
                        } else {
                            echo "<div class='warning'>⚠ Không tìm thấy violation IDs cho payment này</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='warning'>⚠ Lỗi cập nhật violations: " . htmlspecialchars($e->getMessage()) . "</div>";
                        
                        // Thử cập nhật thủ công qua SQL
                        try {
                            $stmt = $pdo->prepare("
                                SELECT GROUP_CONCAT(DISTINCT violation_id) as violation_ids 
                                FROM payments 
                                WHERE payment_group_id = ?
                            ");
                            $stmt->execute([$payment['payment_group_id']]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!empty($result['violation_ids'])) {
                                $violationIdsArray = explode(',', $result['violation_ids']);
                                $placeholders = implode(',', array_fill(0, count($violationIdsArray), '?'));
                                
                                $stmt = $pdo->prepare("
                                    UPDATE violations 
                                    SET trang_thai = 'Đã thanh toán' 
                                    WHERE id IN ($placeholders)
                                ");
                                
                                if ($stmt->execute($violationIdsArray)) {
                                    echo "<div class='success'>✅ Đã cập nhật thủ công " . count($violationIdsArray) . " violations</div>";
                                }
                            }
                        } catch (Exception $sqlError) {
                            echo "<div class='warning'>⚠ Lỗi SQL thủ công: " . htmlspecialchars($sqlError->getMessage()) . "</div>";
                        }
                    }
                    
                    // Tự động reload frontend
                    echo "
                    <script>
                        setTimeout(() => {
                            if (confirm('Cập nhật thành công! Bạn có muốn reload trang chính?')) {
                                if (window.opener && !window.opener.closed) {
                                    window.opener.location.reload();
                                }
                            }
                        }, 2000);
                    </script>";
                    
                } else {
                    echo "<div class='error'>❌ Không thể cập nhật payments trong database</div>";
                }
            } else {
                echo "<div class='error'>❌ Không tìm thấy payment với code: " . htmlspecialchars($paymentCode) . "</div>";
                
                // Hiển thị tất cả payments để debug
                echo "<div class='warning'>🔍 Danh sách tất cả payment codes:</div>";
                $stmt = $pdo->query("
                    SELECT DISTINCT noi_dung_chuyen_khoan, payment_group_id, trang_thai, created_at 
                    FROM payments 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='payment-list'>";
                foreach ($allPayments as $p) {
                    echo "<div class='payment-item'>
                        <div class='payment-code'>" . htmlspecialchars($p['noi_dung_chuyen_khoan']) . "</div>
                        <div><strong>Group ID:</strong> " . htmlspecialchars($p['payment_group_id']) . "</div>
                        <div><strong>Status:</strong> " . htmlspecialchars($p['trang_thai']) . "</div>
                        <div class='timestamp'>Created: " . htmlspecialchars($p['created_at']) . "</div>
                    </div>";
                }
                echo "</div>";
            }
        }
    }
    
    // Hiển thị danh sách payments đang chờ
    $stmt = $pdo->query("
        SELECT DISTINCT payment_group_id, noi_dung_chuyen_khoan, MAX(created_at) as last_created
        FROM payments 
        WHERE trang_thai = 'Chờ thanh toán'
        GROUP BY payment_group_id, noi_dung_chuyen_khoan
        ORDER BY last_created DESC
        LIMIT 15
    ");
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

        <form method="POST" style="margin: 30px 0;">
            <div class="form-group">
                <label for="payment_code">Payment Code (nội dung chuyển khoản):</label>
                <input type="text" id="payment_code" name="payment_code" 
                       placeholder="VP_99C04350_1765947851_9748" 
                       required
                       style="font-family: monospace; font-size: 14px;">
                <small style="color: #666;">Nhập chính xác nội dung chuyển khoản từ QR code</small>
            </div>
            
            <button type="submit" style="background: #28a745; padding: 15px 40px; font-size: 18px;">
                ✅ MÔ PHỎNG WEBHOOK THÀNH CÔNG
            </button>
            
            <button type="button" onclick="testPayment()" style="background: #ff9800; margin-left: 10px; padding: 15px 30px;">
                🧪 TEST VỚI DỮ LIỆU MẪU
            </button>
        </form>
        
        <?php if (!empty($pendingPayments)): ?>
        <div class="payment-list">
            <h3>📋 Payments đang chờ thanh toán (click để chọn):</h3>
            <?php foreach ($pendingPayments as $payment): ?>
            <div class="payment-item" onclick="selectPayment('<?= htmlspecialchars($payment['noi_dung_chuyen_khoan']) ?>')">
                <div class="payment-code"><?= htmlspecialchars($payment['noi_dung_chuyen_khoan']) ?></div>
                <div><strong>Group ID:</strong> <?= htmlspecialchars($payment['payment_group_id']) ?></div>
                <div class="timestamp">Created: <?= htmlspecialchars($payment['last_created']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="warning">📭 Không có payments nào đang chờ thanh toán</div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding: 20px; background: #e7f3ff; border-radius: 8px; border: 1px solid #b8daff;">
            <h3>📌 Hướng dẫn sử dụng:</h3>
            <ol style="line-height: 1.8;">
                <li><strong>Tạo thanh toán:</strong> Vào trang chính, tìm vi phạm và tạo QR code</li>
                <li><strong>Copy Payment Code:</strong> Lấy mã từ QR code (VP_...)</li>
                <li><strong>Paste vào ô trên</strong> hoặc click vào payment trong danh sách</li>
                <li><strong>Click "Mô phỏng webhook thành công"</strong></li>
                <li><strong>Quay lại trang chính</strong> và reload để xem kết quả</li>
            </ol>
            
            <h4 style="margin-top: 20px;">🔧 Test nhanh:</h4>
            <p>Payment code mẫu từ dữ liệu của bạn: <code>VP_99C04350_1765947851_9748</code></p>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
            <h4>⚙️ Debug Info:</h4>
            <p><strong>Session ID:</strong> <?= session_id() ?></p>
            <p><strong>Database Connection:</strong> <?= isset($pdo) ? '✅ Connected' : '❌ Disconnected' ?></p>
            <p><strong>Payment Model:</strong> <?= isset($paymentModel) ? '✅ Loaded' : '❌ Failed' ?></p>
            <p><strong>Violation Model:</strong> <?= isset($violationModel) ? '✅ Loaded' : '❌ Failed' ?></p>
        </div>
    </div>
    
    <script>
    function selectPayment(code) {
        document.getElementById('payment_code').value = code;
        document.getElementById('payment_code').focus();
        document.getElementById('payment_code').scrollIntoView({ behavior: 'smooth' });
    }
    
    function testPayment() {
        // Tạo test payment
        const testCode = 'VP_TEST_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
        
        if (confirm('Tạo payment test: ' + testCode + '?')) {
            document.getElementById('payment_code').value = testCode;
            
            // Tạo form submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="payment_code" value="' + testCode + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Auto-select nếu có parameter
    const urlParams = new URLSearchParams(window.location.search);
    const codeParam = urlParams.get('code');
    if (codeParam) {
        document.getElementById('payment_code').value = codeParam;
        document.getElementById('payment_code').focus();
    }
    
    // Focus vào input khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('payment_code').focus();
    });
    </script>
</body>
</html>