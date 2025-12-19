<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Violation.php';

class PaymentController {
    private $paymentModel;
    private $violationModel;
    
    public function __construct() {
        $this->paymentModel = new Payment();
        $this->violationModel = new Violation();
    }
    
    public function initPayment() {
        header('Content-Type: application/json');
        
        try {
            $input = file_get_contents('php://input');
            
            if (empty($input)) {
                throw new Exception('Không nhận được dữ liệu');
            }
            
            $data = json_decode($input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Dữ liệu JSON không hợp lệ: ' . json_last_error_msg());
            }
            
            $requiredFields = ['violation_ids', 'license_plate', 'amount'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== 0)) {
                    throw new Exception("Thiếu thông tin bắt buộc: $field");
                }
            }
            
            $violationIds = $data['violation_ids'];
            $licensePlate = trim($data['license_plate']);
            $totalAmount = intval($data['amount']);
            
            if (!is_array($violationIds) || empty($violationIds)) {
                throw new Exception('Danh sách vi phạm không hợp lệ');
            }
            
            if ($totalAmount <= 0) {
                throw new Exception('Số tiền không hợp lệ: ' . $totalAmount);
            }
            
            if (empty($licensePlate)) {
                throw new Exception('Biển số xe không được để trống');
            }
            
            $userId = $this->getUserId();
            
            $maThanhToan = 'SEPAY_' . date('YmdHis') . '_' . rand(1000, 9999);
            $paymentCode = 'VP_' . strtoupper($licensePlate) . '_' . time() . '_' . rand(1000, 9999);
            
            $displayAmount = 0;
            $divideRule = 1;
            
            if ($totalAmount >= 2000000) {
                $displayAmount = intval($totalAmount / 1000);
                $divideRule = 1000;
            } else {
                $displayAmount = intval($totalAmount / 500);
                $divideRule = 500;
            }
            
            if ($displayAmount <= 0) {
                $displayAmount = 1;
            }
            
            $createdPayments = [];
            
            foreach ($violationIds as $violationId) {
                $paymentData = [
                    'ma_thanh_toan' => $maThanhToan . '_' . $violationId,
                    'violation_id' => intval($violationId),
                    'user_id' => $userId,
                    'so_tien' => $totalAmount,
                    'so_tien_hien_thi' => $displayAmount,
                    'phep_chia' => $divideRule,
                    'phuong_thuc' => 'SePay',
                    'trang_thai' => 'Chờ thanh toán',
                    'noi_dung_chuyen_khoan' => $paymentCode,
                    'payment_group_id' => $maThanhToan,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $paymentId = $this->paymentModel->createPayment($paymentData);
                
                if (!$paymentId) {
                    $error = $this->paymentModel->getLastError();
                    throw new Exception('Không thể tạo thanh toán cho vi phạm ID: ' . $violationId . ' | Error: ' . $error);
                }
                
                $createdPayments[] = $paymentId;
            }
            
            $yourBankAccount = '96247LVTH1809';
            $yourBankCode = 'BIDV';
            $paymentDescription = urlencode($paymentCode);
            
            $qrCodeUrl = "https://qr.sepay.vn/img?acc={$yourBankAccount}&bank={$yourBankCode}&amount={$displayAmount}&des={$paymentDescription}&template=compact";
            
            $response = [
                'success' => true,
                'payment_id' => $maThanhToan,
                'payment_code' => $paymentCode,
                'qr_code_url' => $qrCodeUrl,
                'account_info' => [
                    'bank_name' => 'BIDV',
                    'account_number' => $yourBankAccount,
                    'account_name' => 'LUU VAN THANH HUY'
                ],
                'amount_display' => number_format($displayAmount, 0, ',', '.') . ' VND',
                'amount_original_formatted' => number_format($totalAmount, 0, ',', '.') . ' VND',
                'transfer_content' => $paymentCode,
                'violation_ids' => $violationIds,
                'total_amount' => $totalAmount,
                'display_amount' => $displayAmount,
                'divide_rule' => $divideRule
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("[Payment] Error: " . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo thanh toán. Vui lòng thử lại.',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function checkPaymentDB() {
        header('Content-Type: application/json');
        
        try {
            $paymentId = $_GET['payment_id'] ?? '';
            
            if (empty($paymentId)) {
                echo json_encode(['success' => false, 'message' => 'Missing payment_id']);
                return;
            }
            
            $paymentGroupInfo = $this->paymentModel->getPaymentGroupInfo($paymentId);
            
            if (!$paymentGroupInfo) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                return;
            }
            
            $status = $paymentGroupInfo['trang_thai'] ?? 'Chờ thanh toán';
            
            $response = [
                'success' => true,
                'status' => $status === 'Thành công' ? 'completed' : 'pending',
                'payment_id' => $paymentId,
                'payment_code' => $paymentGroupInfo['noi_dung_chuyen_khoan'] ?? '',
                'trang_thai_db' => $status,
                'details' => [
                    'thoi_gian_xac_nhan' => $paymentGroupInfo['thoi_gian_xac_nhan'] ?? '',
                    'thoi_gian_thanh_toan' => $paymentGroupInfo['thoi_gian_thanh_toan'] ?? ''
                ]
            ];
            
            if ($status === 'Thành công') {
                $violationIds = $this->paymentModel->getViolationIdsByGroupId($paymentId);
                $response['violation_ids'] = $violationIds;
                $response['transaction_info'] = [
                    'transaction_id' => $paymentGroupInfo['sepay_transaction_id'] ?? 'MANUAL_UPDATE',
                    'amount' => $paymentGroupInfo['so_tien'] ?? 0,
                    'payment_time' => $paymentGroupInfo['thoi_gian_thanh_toan'] ?? date('Y-m-d H:i:s')
                ];
                
                $response['realtime_update'] = true;
                $response['user_message'] = '✅ Thanh toán đã được xác nhận thành công!';
                $response['timestamp'] = date('Y-m-d H:i:s');
                
                $violationDetails = [];
                foreach ($violationIds as $vid) {
                    $violation = $this->violationModel->getViolationById($vid);
                    if ($violation) {
                        $violationDetails[] = [
                            'id' => $vid,
                            'trang_thai' => $violation['trang_thai'] ?? 'Chưa xử lý',
                            'bien_so' => $violation['bien_so'] ?? '',
                            'muc_phat' => $violation['muc_phat'] ?? 0
                        ];
                    }
                }
                $response['violation_details'] = $violationDetails;
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('checkPaymentDB Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function manualWebhook() {
        header('Content-Type: application/json');
        
        try {
            $paymentCode = $_POST['payment_code'] ?? '';
            
            if (empty($paymentCode)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập Payment Code']);
                return;
            }
            
            $payment = $this->paymentModel->getPaymentByContent($paymentCode);
            
            if (!$payment) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy payment với code: ' . $paymentCode]);
                return;
            }
            
            $paymentGroupId = $payment['payment_group_id'];
            
            $updateData = [
                'trang_thai' => 'Thành công',
                'sepay_transaction_id' => 'MANUAL_' . time(),
                'reference_number' => $paymentCode,
                'thoi_gian_xac_nhan' => date('Y-m-d H:i:s'),
                'thoi_gian_thanh_toan' => date('Y-m-d H:i:s')
            ];
            
            $updated = $this->paymentModel->updatePaymentByGroupId($paymentGroupId, $updateData);
            
            if (!$updated) {
                throw new Exception('Không thể cập nhật payments');
            }
            
            $violationIds = $this->paymentModel->getViolationIdsByGroupId($paymentGroupId);
            $updatedViolations = 0;
            
            foreach ($violationIds as $violationId) {
                if ($this->violationModel->updateViolationStatus($violationId, 'Đã thanh toán')) {
                    $updatedViolations++;
                }
            }
            
            $response = [
                'success' => true,
                'message' => '✅ Mô phỏng webhook thành công!',
                'details' => [
                    'payment_group_id' => $paymentGroupId,
                    'violations_updated' => $updatedViolations,
                    'total_violations' => count($violationIds),
                    'update_time' => date('Y-m-d H:i:s')
                ],
                'frontend_update' => [
                    'action' => 'payment_completed',
                    'payment_id' => $paymentGroupId,
                    'violation_ids' => $violationIds,
                    'reload_required' => true,
                    'notification' => 'Thanh toán đã được xác nhận. Trang sẽ tự động cập nhật...'
                ]
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('Manual Webhook Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ]);
        }
    }
    
    public function getPaymentInfo() {
        header('Content-Type: application/json');
        
        try {
            $paymentId = $_GET['payment_id'] ?? '';
            
            if (empty($paymentId)) {
                echo json_encode(['success' => false, 'message' => 'Missing payment_id']);
                return;
            }
            
            $paymentGroupInfo = $this->paymentModel->getPaymentGroupInfo($paymentId);
            
            if (!$paymentGroupInfo) {
                echo json_encode(['success' => false, 'message' => 'Payment not found']);
                return;
            }
            
            $violationIds = $this->paymentModel->getViolationIdsByGroupId($paymentId);
            $violationStatuses = [];
            
            foreach ($violationIds as $vid) {
                $violation = $this->violationModel->getViolationById($vid);
                if ($violation) {
                    $violationStatuses[] = [
                        'id' => $vid,
                        'trang_thai' => $violation['trang_thai'] ?? 'Chưa xử lý',
                        'muc_phat' => $violation['muc_phat'] ?? 0,
                        'bien_so' => $violation['bien_so'] ?? ''
                    ];
                }
            }
            
            $response = [
                'success' => true,
                'payment' => $paymentGroupInfo,
                'violations' => $violationStatuses,
                'all_completed' => $paymentGroupInfo['trang_thai'] === 'Thành công',
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log('getPaymentInfo Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function realtimeStatus() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        
        $paymentId = $_GET['payment_id'] ?? '';
        
        if (empty($paymentId)) {
            echo "data: " . json_encode(['success' => false, 'message' => 'Missing payment_id']) . "\n\n";
            flush();
            return;
        }
        
        $timeout = 30;
        $startTime = time();
        
        while (time() - $startTime < $timeout) {
            $paymentGroupInfo = $this->paymentModel->getPaymentGroupInfo($paymentId);
            
            if ($paymentGroupInfo && $paymentGroupInfo['trang_thai'] === 'Thành công') {
                echo "data: " . json_encode([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed!',
                    'data' => $paymentGroupInfo,
                    'timestamp' => date('Y-m-d H:i:s')
                ]) . "\n\n";
                flush();
                return;
            }
            
            echo "data: " . json_encode([
                'success' => true,
                'status' => 'pending',
                'message' => 'Waiting...',
                'timestamp' => date('Y-m-d H:i:s')
            ]) . "\n\n";
            flush();
            
            sleep(2);
        }
        
        echo "data: " . json_encode([
            'success' => true,
            'status' => 'timeout',
            'message' => 'Connection timeout',
            'timestamp' => date('Y-m-d H:i:s')
        ]) . "\n\n";
        flush();
    }
    
    public function checkPaymentStatus() {
        header('Content-Type: application/json');
        
        try {
            $paymentCode = $_GET['payment_code'] ?? '';
            $paymentId = $_GET['payment_id'] ?? '';
            
            if (empty($paymentId)) {
                throw new Exception('Thiếu thông tin thanh toán');
            }
            
            $paymentGroupInfo = $this->paymentModel->getPaymentGroupInfo($paymentId);
            
            if (!$paymentGroupInfo) {
                throw new Exception('Không tìm thấy thông tin thanh toán');
            }
            
            $status = $paymentGroupInfo['trang_thai'] ?? 'Chờ thanh toán';
            
            if ($status === 'Thành công') {
                $response = [
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Thanh toán thành công!',
                    'details' => [
                        'payment_id' => $paymentId,
                        'confirmed_at' => $paymentGroupInfo['thoi_gian_xac_nhan'] ?? date('Y-m-d H:i:s')
                    ]
                ];
                
                $violationIds = $this->paymentModel->getViolationIdsByGroupId($paymentId);
                if (!empty($violationIds)) {
                    $response['violation_ids'] = $violationIds;
                }
                
                echo json_encode($response);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'status' => 'pending',
                'message' => 'Đang chờ thanh toán...',
                'next_check' => date('Y-m-d H:i:s', time() + 5)
            ]);
            
        } catch (Exception $e) {
            error_log('Check Payment Error: ' . $e->getMessage());
            
            echo json_encode([
                'success' => true,
                'status' => 'pending',
                'message' => 'Đang kiểm tra...'
            ]);
        }
    }
    
    private function getUserId() {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            return intval($_SESSION['user_id']);
        }
        return 1;
    }
}

if (isset($_GET['action'])) {
    $controller = new PaymentController();
    
    switch ($_GET['action']) {
        case 'init':
            $controller->initPayment();
            break;
            
        case 'check_db':
            $controller->checkPaymentDB();
            break;
            
        case 'manual_webhook':
            $controller->manualWebhook();
            break;
            
        case 'get_payment_info':
            $controller->getPaymentInfo();
            break;
            
        case 'realtime_status':
            $controller->realtimeStatus();
            break;
            
        case 'check':
            $controller->checkPaymentStatus();
            break;
            
        case 'test':
            echo json_encode(['success' => true, 'message' => 'API Working']);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action không tồn tại']);
            break;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng chỉ định action']);
}
?>