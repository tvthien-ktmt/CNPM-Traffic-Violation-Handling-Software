<?php
class ViolationController {
    private $violationModel;
    private $vehicleModel;

    public function __construct() {
        $this->violationModel = new Violation();
        $this->vehicleModel = new Vehicle();
    }

    // Hiển thị trang tra cứu
    public function showSearch() {
        include 'app/views/violations/search.php';
    }

    // Xử lý tra cứu vi phạm
    public function search() {
        $licensePlate = $_POST['license_plate'] ?? '';
        $vehicleType = $_POST['vehicle_type'] ?? '1';
        $violations = [];

        if (!empty($licensePlate)) {
            // Chuẩn hóa biển số
            $licensePlate = strtoupper(str_replace(' ', '', $licensePlate));
            $violations = $this->violationModel->getViolationsByLicensePlate($licensePlate);
        }

        // Nếu là AJAX request, chỉ trả về phần kết quả
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            
            ob_start();
            if (!empty($violations)) {
                echo '<div class="violation-results">';
                echo '<h3>Kết quả tra cứu cho biển số: ' . htmlspecialchars($licensePlate) . '</h3>';
                foreach ($violations as $violation) {
                    echo '<div class="violation-item" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: white; color: black;">';
                    echo '<p><strong>Thời gian:</strong> ' . $violation['violation_date'] . '</p>';
                    echo '<p><strong>Địa điểm:</strong> ' . $violation['location'] . '</p>';
                    echo '<p><strong>Lỗi vi phạm:</strong> ' . $violation['violation_type'] . '</p>';
                    echo '<p><strong>Mức phạt:</strong> ' . number_format($violation['fine_amount']) . ' VNĐ</p>';
                    echo '<p><strong>Trạng thái:</strong> ';
                    echo '<span style="color: ' . ($violation['status'] == 'paid' ? 'green' : 'red') . '">';
                    echo $violation['status'] == 'paid' ? 'Đã nộp phạt' : 'Chưa nộp phạt';
                    echo '</span></p>';
                    echo '</div>';
                }
                echo '</div>';
            } elseif (!empty($licensePlate)) {
                echo '<p>Không tìm thấy vi phạm nào cho biển số: ' . htmlspecialchars($licensePlate) . '</p>';
            }
            $output = ob_get_clean();
            echo $output;
            exit;
        }

        // Nếu là normal request, hiển thị toàn bộ trang
        include 'app/views/violations/search.php';
    }

    // API cho chatbot tra cứu mức phạt
    public function apiChatbot() {
        $question = $_POST['question'] ?? '';
        
        if (empty($question)) {
            echo json_encode(['error' => 'Câu hỏi không được để trống']);
            exit;
        }

        // Gửi câu hỏi đến AI service
        $aiResponse = $this->queryAIService($question);
        
        header('Content-Type: application/json');
        echo json_encode(['answer' => $aiResponse]);
    }

    private function queryAIService($question) {
        // Gọi API Python AI service
        $url = 'http://localhost:5000/api/query-laws';
        $data = json_encode(['question' => $question]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        return $result['answer'] ?? 'Xin lỗi, tôi không thể trả lời câu hỏi này ngay lúc này.';
    }
}