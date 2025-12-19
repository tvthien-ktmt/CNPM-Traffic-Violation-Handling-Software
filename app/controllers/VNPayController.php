<?php
require_once __DIR__ . '/../models/Violation.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../libraries/VNPay.php';

class VNPayController {
    private $violationModel;
    private $paymentModel;
    private $vnpay;

    public function __construct() {
        $this->violationModel = new Violation();
        $this->paymentModel = new Payment();
        
        // Load cấu hình VNPay
        $vnpayConfig = require_once __DIR__ . '/../../config/vnpay_config.php';
        $this->vnpay = new VNPay($vnpayConfig);
    }

    // Tạo thanh toán
    public function createPayment(array $data) {
        try {
            // Validate dữ liệu
            if (empty($data['violation_ids']) || empty($data['license_plate']) || empty($data['amount'])) {
                return [
                    'success' => false,
                    'message' => 'Thiếu thông tin thanh toán'
                ];
            }

            // Kiểm tra vi phạm có tồn tại
            foreach ($data['violation_ids'] as $violationId) {
                $violation = $this->violationModel->getViolationById($violationId);
                if (!$violation) {
                    return [
                        'success' => false,
                        'message' => "Vi phạm #$violationId không tồn tại"
                    ];
                }
            }

            // Tạo mã giao dịch
            $vnpTxnRef = date('YmdHis') . rand(1000, 9999);
            
            // Tạo payment record
            $paymentId = $this->paymentModel->createPayment([
                'violation_ids' => $data['violation_ids'],
                'license_plate' => $data['license_plate'],
                'total_amount' => $data['amount'],
                'vnp_txnref' => $vnpTxnRef
            ]);

            if (!$paymentId) {
                return [
                    'success' => false,
                    'message' => 'Không thể tạo thanh toán'
                ];
            }

            // Tạo URL thanh toán VNPay
            $orderInfo = "Thanh toán vi phạm biển số: " . $data['license_plate'];
            if (count($data['violation_ids']) === 1) {
                $violation = $this->violationModel->getViolationById($data['violation_ids'][0]);
                $orderInfo = "Thanh toán vi phạm: " . ($violation['ten_loi'] ?? 'Vi phạm giao thông');
            }

            $paymentUrl = $this->vnpay->createPaymentUrl([
                'order_id' => $vnpTxnRef,
                'amount' => $data['amount'],
                'order_desc' => $orderInfo
            ]);

            return [
                'success' => true,
                'payment_url' => $paymentUrl,
                'payment_id' => $paymentId,
                'txn_ref' => $vnpTxnRef
            ];

        } catch (Exception $e) {
            error_log('VNPayController::createPayment error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    // Xử lý callback từ VNPay
    public function handleCallback() {
        try {
            $vnpData = $_GET;
            
            // Xác minh chữ ký
            if (!$this->vnpay->verifyPayment($vnpData)) {
                return [
                    'success' => false,
                    'message' => 'Chữ ký không hợp lệ'
                ];
            }

            $vnpTxnRef = $vnpData['vnp_TxnRef'] ?? '';
            $vnpResponseCode = $vnpData['vnp_ResponseCode'] ?? '';
            
            // Lấy thông tin payment
            $payment = $this->paymentModel->getPaymentByTxnRef($vnpTxnRef);
            
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch'
                ];
            }

            // Kiểm tra response code
            if ($vnpResponseCode === '00') {
                // THÀNH CÔNG
                $violationIds = json_decode($payment['violation_ids'], true);
                
                // Cập nhật trạng thái vi phạm
                $updateSuccess = $this->violationModel->payMultipleViolations($violationIds);
                
                if ($updateSuccess) {
                    // Cập nhật trạng thái payment
                    $this->paymentModel->updatePaymentStatus($vnpTxnRef, 'success', $vnpData);
                    
                    return [
                        'success' => true,
                        'message' => 'Thanh toán thành công',
                        'payment_code' => $payment['payment_code'],
                        'license_plate' => $payment['license_plate'],
                        'amount' => $payment['total_amount'],
                        'violation_ids' => $violationIds
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Không thể cập nhật trạng thái vi phạm'
                    ];
                }
            } else {
                // THẤT BẠI
                $this->paymentModel->updatePaymentStatus($vnpTxnRef, 'failed', $vnpData);
                
                $errorMessages = [
                    '01' => 'Giao dịch bị từ chối bởi ngân hàng',
                    '02' => 'Ngân hàng từ chối thanh toán',
                    '03' => 'Mã đơn vị không tồn tại',
                    '04' => 'Không đúng số dư',
                    '05' => 'Thông tin tài khoản không đúng',
                    '06' => 'Thiếu thông tin bắt buộc',
                    '07' => 'Lỗi định dạng',
                    '09' => 'Yêu cầu bị từ chối',
                    '10' => 'Đã hết hạn',
                    '11' => 'Tài khoản bị khóa',
                    '12' => 'Sai OTP',
                    '13' => 'Sai chữ ký',
                    '24' => 'Giao dịch bị hủy',
                    '51' => 'Không đủ tiền',
                    '65' => 'Vượt quá hạn mức',
                    '75' => 'Ngân hàng đang bảo trì',
                    '79' => 'Sai mật khẩu thanh toán',
                    '99' => 'Lỗi khác'
                ];
                
                $errorMessage = $errorMessages[$vnpResponseCode] ?? 'Thanh toán thất bại';
                
                return [
                    'success' => false,
                    'message' => $errorMessage . ' (Mã lỗi: ' . $vnpResponseCode . ')'
                ];
            }

        } catch (Exception $e) {
            error_log('VNPayController::handleCallback error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi xử lý callback: ' . $e->getMessage()
            ];
        }
    }
}

// Xử lý request API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $controller = new VNPayController();
    
    switch ($_GET['action']) {
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode($controller->createPayment($input));
            break;
            
        case 'callback':
            // Được gọi từ VNPay
            echo json_encode($controller->handleCallback());
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    exit;
}
?>