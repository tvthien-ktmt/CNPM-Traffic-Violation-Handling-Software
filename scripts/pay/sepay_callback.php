<?php
/**
 * SePay Callback Handler
 * File: scripts/pay/sepay_callback.php
 * 
 * URL Webhook: https://yourdomain.com/scripts/pay/sepay_callback.php
 */

require_once __DIR__ . '/../../app/models/Database.php';
require_once __DIR__ . '/../../app/models/Payment.php';
require_once __DIR__ . '/../../app/models/Violation.php';
require_once __DIR__ . '/../../app/services/SePayService.php';

// Log file
$logFile = __DIR__ . '/../../logs/sepay_webhook.log';

function logWebhook($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

try {
    // Lấy raw input
    $input = file_get_contents('php://input');
    logWebhook("Received webhook: " . $input);
    
    // Parse JSON
    $data = json_decode($input, true);
    
    if (!$data) {
        logWebhook("ERROR: Invalid JSON");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    // Lấy thông tin cần thiết
    $transactionContent = $data['transaction_content'] ?? '';
    $amountIn = (int)($data['amount_in'] ?? 0);
    $transactionId = $data['id'] ?? null;
    $transactionDate = $data['transaction_date'] ?? date('Y-m-d H:i:s');
    $bankBrandName = $data['bank_brand_name'] ?? 'Unknown';
    
    logWebhook("Transaction content: {$transactionContent}");
    logWebhook("Amount: {$amountIn}");
    
    // Khởi tạo services
    $sepayService = new SePayService();
    $paymentModel = new Payment();
    $violationModel = new Violation();
    
    // Parse transaction code từ nội dung
    $transactionCode = $sepayService->parseTransactionCode($transactionContent);
    
    if (!$transactionCode) {
        logWebhook("ERROR: Cannot parse transaction code from content");
        
        // Lưu vào webhook log table để kiểm tra sau
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO sepay_webhooks (transaction_content, amount, raw_data, processed, created_at)
                VALUES (?, ?, ?, FALSE, NOW())
            ");
            $stmt->execute([$transactionContent, $amountIn, $input]);
        } catch (Exception $e) {
            logWebhook("ERROR saving to webhook log: " . $e->getMessage());
        }
        
        http_response_code(200); // Vẫn trả về 200 để SePay không gửi lại
        echo json_encode(['success' => false, 'message' => 'Cannot parse transaction code']);
        exit;
    }
    
    logWebhook("Parsed transaction code: {$transactionCode}");
    
    // Tìm payment trong database
    $payment = $paymentModel->getPaymentByTransactionCode($transactionCode);
    
    if (!$payment) {
        logWebhook("ERROR: Payment not found for transaction code: {$transactionCode}");
        
        // Lưu vào webhook log
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO sepay_webhooks (transaction_content, amount, raw_data, processed, created_at)
                VALUES (?, ?, ?, FALSE, NOW())
            ");
            $stmt->execute([$transactionContent, $amountIn, $input]);
        } catch (Exception $e) {
            logWebhook("ERROR saving to webhook log: " . $e->getMessage());
        }
        
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }
    
    logWebhook("Found payment ID: {$payment['id']}, Status: {$payment['status']}");
    
    // Kiểm tra số tiền
    $expectedAmount = (int)$payment['amount'];
    
    if ($amountIn < $expectedAmount) {
        logWebhook("ERROR: Amount mismatch. Expected: {$expectedAmount}, Received: {$amountIn}");
        
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
        exit;
    }
    
    // Kiểm tra trạng thái payment
    if ($payment['status'] === 'completed') {
        logWebhook("WARNING: Payment already completed");
        
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Payment already completed']);
        exit;
    }
    
    // CẬP NHẬT TRẠNG THÁI THANH TOÁN
    $updateSuccess = $paymentModel->updatePaymentStatus($payment['id'], 'completed', [
        'sepay_transaction_id' => $transactionId,
        'paid_amount' => $amountIn,
        'paid_at' => $transactionDate
    ]);
    
    if (!$updateSuccess) {
        logWebhook("ERROR: Failed to update payment status");
        throw new Exception('Failed to update payment status');
    }
    
    logWebhook("Payment status updated to completed");
    
    // ĐÁNH DẤU CÁC VI PHẠM ĐÃ THANH TOÁN
    $violationIds = json_decode($payment['violation_ids'], true);
    
    if (!empty($violationIds)) {
        foreach ($violationIds as $violationId) {
            $markSuccess = $violationModel->markAsPaid((int)$violationId);
            if ($markSuccess) {
                logWebhook("Violation ID {$violationId} marked as paid");
            } else {
                logWebhook("WARNING: Failed to mark violation ID {$violationId} as paid");
            }
        }
    }
    
    // LƯU VÀO WEBHOOK LOG
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO sepay_webhooks (payment_id, transaction_content, amount, raw_data, processed, created_at)
            VALUES (?, ?, ?, ?, TRUE, NOW())
        ");
        $stmt->execute([$payment['id'], $transactionContent, $amountIn, $input]);
    } catch (Exception $e) {
        logWebhook("ERROR saving to webhook log: " . $e->getMessage());
    }
    
    // GỬI EMAIL THÔNG BÁO (nếu có email trong payment hoặc violation)
    // TODO: Implement email notification
    
    logWebhook("Webhook processed successfully");
    
    // Trả về success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'payment_id' => $payment['id'],
        'transaction_code' => $transactionCode
    ]);
    
} catch (Exception $e) {
    logWebhook("EXCEPTION: " . $e->getMessage());
    logWebhook("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}