<?php
class SePay {
    private $config;
    private $apiUrl = 'https://api.sepay.vn/v1'; // Example URL - check actual SePay API
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Tạo QR code thanh toán
     */
    public function createQRPayment($data) {
        try {
            // Prepare API request
            $requestData = [
                'api_key' => $this->config['api_key'],
                'secret_key' => $this->config['secret_key'],
                'amount' => $data['amount'],
                'order_id' => $data['payment_code'],
                'order_info' => $data['description'],
                'bank_code' => 'ALL', // All banks
                'return_url' => $data['return_url'],
                'notify_url' => $data['callback_url'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone']
            ];
            
            // Call SePay API
            $result = $this->callApi('/qr/create', $requestData);
            
            if ($result && $result['code'] == 0) {
                return [
                    'success' => true,
                    'data' => [
                        'transaction_code' => $result['data']['transaction_code'],
                        'qr_code_url' => $result['data']['qr_code_url'],
                        'bank_name' => $result['data']['bank_name'],
                        'account_number' => $result['data']['account_number'],
                        'account_name' => $result['data']['account_name'],
                        'transfer_content' => $result['data']['transfer_content'] ?? $data['payment_code']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create QR code'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SePay API error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kiểm tra trạng thái thanh toán
     */
    public function checkStatus($transactionCode) {
        try {
            $requestData = [
                'api_key' => $this->config['api_key'],
                'secret_key' => $this->config['secret_key'],
                'transaction_code' => $transactionCode
            ];
            
            $result = $this->callApi('/transaction/status', $requestData);
            
            if ($result && $result['code'] == 0) {
                return [
                    'success' => true,
                    'data' => [
                        'status' => $result['data']['status'],
                        'transaction_info' => $result['data']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to check status'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'SePay API error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Gọi API SePay
     */
    private function callApi($endpoint, $data) {
        $url = $this->apiUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }
}