<?php
/**
 * SEPAY WEBHOOK CONTROLLER
 * Nhận thông báo từ SePay khi có giao dịch thành công
 * 
 * Endpoint: https://yourdomain.com/traffic/app/controllers/SepayWebhookController.php
 */

// Bật log lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/sepay_webhook.log');

// Load dependencies
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Violation.php';
require_once __DIR__ . '/../../config/sepay_config.php';

class SepayWebhookController {
    private $paymentModel;
    private $violationModel;
    private $config;
    
    public function __construct() {
        $this->paymentModel = new Payment();
        $this->violationModel = new Violation();
        $this->config = require __DIR__ . '/../../config/sepay_config.php';
    }
    
    /**
     * Xử lý webhook từ SePay
     */
    public function handleWebhook() {
        header('Content-Type: application/json');
        
        try {
            // 1. LẤY DỮ LIỆU WEBHOOK
            $rawData = file_get_contents('php://input');
            
            error_log('=== SEPAY WEBHOOK RECEIVED ===');
            error_log('Raw data: ' . $rawData);
            
            if (empty($rawData)) {
                throw new Exception('No webhook data received');
            }
            
            $webhookData = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            error_log('Parsed data: ' . print_r($webhookData, true));
            
            // 2. XÁC THỰC WEBHOOK (Kiểm tra header Authorization nếu có)
            $this->validateWebhook();
            
            // 3. TRÍCH XUẤT THÔNG TIN
            $transactionId = $webhookData['id'] ?? '';
            $gateway = $webhookData['gateway'] ?? '';
            $transactionDate = $webhookData['transactionDate'] ?? '';
            $accountNumber = $webhookData['accountNumber'] ?? '';
            $transferType = $webhookData['transferType'] ?? '';
            $transferAmount = floatval($webhookData['transferAmount'] ?? 0);
            $accumulated = floatval($webhookData['accumulated'] ?? 0);
            $content = $webhookData['content'] ?? '';
            $referenceCode = $webhookData['referenceCode'] ?? '';
            
            error_log('Transaction ID: ' . $transactionId);
            error_log('Amount: ' . $transferAmount);
            error_log('Content: ' . $content);
            
            // 4. KIỂM TRA GIAO DỊCH HỢP LỆ
            if ($transferType !== 'in') {
                throw new Exception('Not an incoming transaction');
            }
            
            if ($transferAmount <= 0) {
                throw new Exception('Invalid transfer amount: ' . $transferAmount);
            }
            
            // 5. BÓC TÁCH MÃ THANH TOÁN TỪ NỘI DUNG
            // Ví dụ: "VP_89H0227_1734567890_1234" hoặc "THANH TOAN VP_89H0227_1734567890_1234"
            preg_match('/VP_[A-Z0-9]+_\d+_\d+/', $content, $matches);
            
            if (empty($matches)) {
                error_log('Payment code not found in content: ' . $content);
                throw new Exception('Payment code not found in transaction content');
            }
            
            $paymentCode = $matches[0];
            error_log('Payment code found: ' . $paymentCode);
            
            // 6. TÌM THANH TOÁN TRONG DATABASE
            $payment = $this->paymentModel->getPaymentByContent($paymentCode);
            
            if (!$payment) {
                error_log('Payment not found for code: ' . $paymentCode);
                throw new Exception('Payment not found');
            }
            
            error_log('Payment found - ID: ' . $payment['id'] . ', Group: ' . $payment['payment_group_id']);
            
            // 7. KIỂM TRA TRẠNG THÁI THANH TOÁN
            if ($payment['trang_thai'] === 'Thành công') {
                error_log('Payment already completed - ID: ' . $payment['id']);
                // Trả về success để SePay không gọi lại
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment already processed'
                ]);
                return;
            }
            
            // 8. XÁC ĐỊNH QUY TẮC CHIA
            $paymentGroupId = $payment['payment_group_id'];
            $totalAmountInGroup = $this->paymentModel->getTotalAmountByGroupId($paymentGroupId);
            
            error_log('Total amount in group: ' . $totalAmountInGroup);
            
            // Lấy số tiền gốc (chưa chia) từ database
            // Giả sử bạn lưu số tiền gốc vào cột riêng, hoặc tính ngược lại
            $originalAmount = $totalAmountInGroup; // Số tiền trong DB là đã chia
            
            // Xác định quy tắc chia dựa trên số tiền gốc
            if ($originalAmount >= 2000) { // 2000 VND trong DB = 2,000,000 VND gốc (đã chia 1000)
                $divideRule = 1000;
                $expectedAmount = $originalAmount; // Số tiền DB đã chia
            } else if ($originalAmount >= 4) { // 4 VND trong DB = 2,000 VND gốc (đã chia 500)
                $divideRule = 500;
                $expectedAmount = $originalAmount; // Số tiền DB đã chia
            } else {
                $divideRule = 1;
                $expectedAmount = $originalAmount;
            }
            
            error_log('Divide rule: ' . $divideRule);
            error_log('Expected amount: ' . $expectedAmount);
            error_log('Received amount: ' . $transferAmount);
            
            // 9. KIỂM TRA SỐ TIỀN KHỚP
            // Chấp nhận sai lệch nhỏ do làm tròn
            $tolerance = 0.01; // 0.01 VND
            if (abs($transferAmount - $expectedAmount) > $tolerance) {
                error_log('Amount mismatch - Expected: ' . $expectedAmount . ', Received: ' . $transferAmount);
                throw new Exception('Payment amount does not match. Expected: ' . $expectedAmount . ', Received: ' . $transferAmount);
            }
            
            error_log('Amount matched!');
            
            // 10. CẬP NHẬT TRẠNG THÁI THANH TOÁN
            $updateData = [
                'trang_thai' => 'Thành công',
                'sepay_transaction_id' => $transactionId,
                'sepay_reference_number' => $referenceCode,
                'reference_number' => $referenceCode,
                'thoi_gian_xac_nhan' => date('Y-m-d H:i:s'),
                'thoi_gian_thanh_toan' => $transactionDate,
                'gateway' => $gateway,
                'accumulated' => $accumulated
            ];
            
            // Cập nhật tất cả payment trong group
            $updated = $this->paymentModel->updatePaymentByGroupId($paymentGroupId, $updateData);
            
            if (!$updated) {
                throw new Exception('Failed to update payment status');
            }
            
            error_log('Payment updated successfully');
            
            // 11. CẬP NHẬT TRẠNG THÁI VI PHẠM
            $violations = $this->paymentModel->getViolationIdsByGroupId($paymentGroupId);
            
            foreach ($violations as $violationId) {
                $this->violationModel->updateViolationStatus($violationId, 'Đã thanh toán');
                error_log('Violation updated - ID: ' . $violationId);
            }
            
            error_log('=== WEBHOOK PROCESSED SUCCESSFULLY ===');
            
            // 12. TRẢ VỀ THÀNH CÔNG CHO SEPAY
            echo json_encode([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $paymentGroupId,
                'transaction_id' => $transactionId
            ]);
            
        } catch (Exception $e) {
            error_log('=== WEBHOOK ERROR ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            
            // Trả về lỗi để SePay retry
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Xác thực webhook (kiểm tra API Key nếu cấu hình)
     */
    private function validateWebhook() {
        // Kiểm tra Authorization header nếu có cấu hình
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!empty($this->config['webhook_secret'])) {
            $expectedAuth = 'Bearer ' . $this->config['webhook_secret'];
            
            if ($authHeader !== $expectedAuth) {
                throw new Exception('Unauthorized webhook request');
            }
        }
    }
}

// ============== ROUTE HANDLER ==============
$controller = new SepayWebhookController();
$controller->handleWebhook();
?>