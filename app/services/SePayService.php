<?php
/**
 * SePay Payment Service
 * File: app/services/SePayService.php
 * 
 * Tích hợp API SePay để tạo QR thanh toán tự động
 * Docs: https://sepay.vn/tai-lieu-api
 */

class SePayService {
    private $apiToken;
    private $accountNumber;
    private $accountName;
    private $baseUrl = 'https://my.sepay.vn/userapi';
    
    public function __construct() {
        // Lấy config từ file hoặc .env
        $config = require __DIR__ . '/../../config/payment_config.php';
        
        $this->apiToken = $config['sepay_api_token'];
        $this->accountNumber = $config['sepay_account_number'];
        $this->accountName = $config['sepay_account_name'];
    }
    
    /**
     * Tạo QR code thanh toán
     * SePay sử dụng VietQR standard
     */
    public function createQRPayment($paymentData) {
        try {
            $amount = $paymentData['amount'];
            $transactionCode = $paymentData['ma_thanh_toan'];
            $description = $paymentData['description'];
            
            // Tạo nội dung chuyển khoản (mã giao dịch để đối chiếu)
            $content = $this->normalizeContent($transactionCode);
            
            // Tạo URL QR theo chuẩn VietQR
            $qrData = [
                'accountNo' => $this->accountNumber,
                'accountName' => $this->accountName,
                'acqId' => '970422', // Mã ngân hàng MB Bank (hoặc ngân hàng bạn dùng)
                'amount' => $amount,
                'addInfo' => $content,
                'format' => 'text',
                'template' => 'compact'
            ];
            
            // Generate QR URL
            $qrUrl = $this->generateVietQRUrl($qrData);
            
            // Lưu webhook để check thanh toán
            $this->registerWebhook($transactionCode, $amount);
            
            return [
                'success' => true,
                'qr_code_url' => $qrUrl,
                'qr_data' => $qrData,
                'transaction_code' => $transactionCode,
                'amount' => $amount,
                'content' => $content,
                'account_number' => $this->accountNumber,
                'account_name' => $this->accountName
            ];
            
        } catch (Exception $e) {
            error_log("SePay Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate VietQR URL
     */
    private function generateVietQRUrl($data) {
        $baseUrl = 'https://img.vietqr.io/image';
        
        // Tạo URL theo format VietQR
        $url = sprintf(
            '%s/%s-%s-%s.jpg?amount=%d&addInfo=%s&accountName=%s',
            $baseUrl,
            $data['acqId'],
            $data['accountNo'],
            $data['template'],
            $data['amount'],
            urlencode($data['addInfo']),
            urlencode($data['accountName'])
        );
        
        return $url;
    }
    
    /**
     * Chuẩn hóa nội dung chuyển khoản (không dấu, viết hoa)
     */
    private function normalizeContent($text) {
        $text = strtoupper($text);
        $text = str_replace(' ', '', $text);
        return $text;
    }
    
    /**
     * Đăng ký webhook để nhận thông báo khi có giao dịch
     */
    private function registerWebhook($transactionCode, $amount) {
        // SePay sẽ tự động gọi webhook khi phát hiện giao dịch
        // Webhook URL được cấu hình trong dashboard SePay
        return true;
    }
    
    /**
     * Kiểm tra trạng thái giao dịch qua API SePay
     */
    public function checkTransactionStatus($transactionCode, $amount) {
        try {
            $content = $this->normalizeContent($transactionCode);
            
            // Gọi API lấy lịch sử giao dịch
            $url = $this->baseUrl . '/transactions/list';
            
            $response = $this->callApi($url, 'GET', [
                'limit' => 50
            ]);
            
            if (!$response || !isset($response['transactions'])) {
                return [
                    'status' => 'pending',
                    'found' => false
                ];
            }
            
            // Tìm giao dịch khớp với mã và số tiền
            foreach ($response['transactions'] as $transaction) {
                $transContent = $this->normalizeContent($transaction['transaction_content'] ?? '');
                $transAmount = $transaction['amount_in'] ?? 0;
                
                if (strpos($transContent, $content) !== false && $transAmount >= $amount) {
                    return [
                        'status' => 'success',
                        'found' => true,
                        'transaction_id' => $transaction['id'],
                        'transaction_date' => $transaction['transaction_date'],
                        'amount' => $transAmount,
                        'content' => $transaction['transaction_content']
                    ];
                }
            }
            
            return [
                'status' => 'pending',
                'found' => false
            ];
            
        } catch (Exception $e) {
            error_log("Check status error: " . $e->getMessage());
            return [
                'status' => 'error',
                'found' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Gọi API SePay
     */
    private function callApi($url, $method = 'GET', $data = []) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP $httpCode - $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Xử lý webhook callback từ SePay
     */
    public function handleWebhook($webhookData) {
        try {
            // Verify signature (nếu SePay có)
            if (!$this->verifyWebhookSignature($webhookData)) {
                return [
                    'success' => false,
                    'error' => 'Invalid signature'
                ];
            }
            
            // Parse thông tin giao dịch
            $transactionContent = $webhookData['transaction_content'] ?? '';
            $amount = $webhookData['amount_in'] ?? 0;
            $transactionId = $webhookData['id'] ?? '';
            
            return [
                'success' => true,
                'transaction_content' => $transactionContent,
                'amount' => $amount,
                'transaction_id' => $transactionId,
                'transaction_date' => $webhookData['transaction_date'] ?? ''
            ];
            
        } catch (Exception $e) {
            error_log("Webhook error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature($data) {
        // Implement signature verification nếu SePay có
        // Hiện tại return true
        return true;
    }
    
    /**
     * Lấy thông tin tài khoản
     */
    public function getAccountInfo() {
        return [
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
            'bank_code' => '970422' // MB Bank
        ];
    }
}
?>