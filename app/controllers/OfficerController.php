<?php
session_start();

// ============ CẤU HÌNH TCPDF ============
// Dùng TCPDF thay vì FPDF
$tcpdf_path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/libs/tcpdf/tcpdf.php';

if (!file_exists($tcpdf_path)) {
    die("Lỗi: Không tìm thấy TCPDF. Vui lòng kiểm tra đường dẫn: $tcpdf_path");
}

require_once($tcpdf_path);

// ============ CLASS PDF BIÊN BẢN THỦ CÔNG ============
class ManualViolationPDF extends TCPDF {
    private $data;
    private $lineHeight = 7; // Chiều cao mỗi dòng
    private $sectionSpacing = 4; // Khoảng cách giữa các section
    
    function __construct($data) {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $this->data = $data;
        
        // Cấu hình
        $this->SetCreator('Hệ thống CSGT');
        $this->SetAuthor('Cán bộ CSGT');
        $this->SetTitle('Biên bản vi phạm số ' . $data['report_number']);
        $this->SetSubject('Biên bản vi phạm hành chính');
        
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        
        // Tăng margins để có nhiều không gian hơn
        $this->SetMargins(15, 25, 15); // Top: 25mm để header không đè content
        $this->SetAutoPageBreak(true, 20); // Bottom margin: 20mm
        
        // Thêm trang
        $this->AddPage();
        $this->createDocument();
    }
    
    public function Header() {
        // Chỉ header cho trang đầu
        if ($this->page == 1) {
            // Quốc hiệu
            $this->SetFont('dejavuserif', 'B', 12);
            $this->Cell(0, 3, 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 0, 1, 'C');
            $this->SetFont('dejavuserif', 'B', 10);
            $this->Cell(0, 2, 'Độc lập - Tự do - Hạnh phúc', 0, 1, 'C');
            $this->Ln(2);
            
            // Số biên bản
            $this->SetFont('dejavuserif', 'B', 11);
            $this->Cell(10, 3, 'Số:', 0, 0, 'L');
            $this->SetFont('dejavuserif', 'B', 12);
            $this->Cell(0, 3, $this->data['report_number'], 0, 1, 'L');
            $this->Ln(2);
            
            // Tiêu đề chính
            $this->SetFont('dejavuserif', 'B', 14);
            $this->Cell(0, 5, 'BIÊN BẢN VI PHẠM HÀNH CHÍNH', 0, 1, 'C');
            $this->Ln(3);
        }
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavuserif', 'I', 8);
        $this->Cell(0, 10, 'Trang ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    function createDocument() {
        $this->SetFont('dejavuserif', '', 11);
        
        // ===== PHẦN 1: THÔNG TIN THỜI GIAN, ĐỊA ĐIỂM =====
        $this->Cell(0, $this->lineHeight, 'Căn cứ: ' . $this->data['legal_basis'], 0, 1);
        
        $violation_time = !empty($this->data['violation_time']) ? 
                         date('H:i', strtotime($this->data['violation_time'])) : 
                         date('H:i');
        $violation_date = !empty($this->data['violation_date']) ? 
                         date('d/m/Y', strtotime($this->data['violation_date'])) : 
                         date('d/m/Y');
        
        $this->Cell(0, $this->lineHeight, 'Hồi ' . $violation_time . ' phút, ngày ' . $violation_date, 0, 1);
        $this->Cell(0, $this->lineHeight, 'Tại: ' . $this->data['violation_location'], 0, 1);
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 2: THÔNG TIN CÁN BỘ =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Chúng tôi gồm:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        $this->Cell(0, $this->lineHeight, '1. ' . $this->data['officer_name'] . ' - ' . $this->data['officer_position'], 0, 1);
        $this->Cell(0, $this->lineHeight, '   Đơn vị: ' . $this->data['officer_unit'], 0, 1);
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 3: THÔNG TIN NGƯỜI VI PHẠM =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Tiến hành lập biên bản về vi phạm hành chính đối với:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        // Sử dụng Cell với width cố định để căn chỉnh
        $labelWidth = 60;
        $contentStart = 70;
        
        $this->Cell($labelWidth, $this->lineHeight, 'Ông (Bà):', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['violator_name'], 0, 1);
        
        if (!empty($this->data['violator_birthday'])) {
            $birthday = date('d/m/Y', strtotime($this->data['violator_birthday']));
        } else {
            $birthday = '';
        }
        $this->Cell($labelWidth, $this->lineHeight, 'Sinh ngày:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $birthday, 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Quốc tịch:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['violator_nationality'], 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Nghề nghiệp:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, 'Lái xe', 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Nơi ở hiện tại:', 0, 0);
        $this->SetX($contentStart);
        $this->MultiCell(0, $this->lineHeight, $this->data['violator_address'], 0, 'L');
        
        $this->Cell($labelWidth, $this->lineHeight, 'CMND/CCCD số:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['violator_id'], 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Ngày cấp:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, date('d/m/Y'), 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Nơi cấp:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, 'Công an TP Hà Nội', 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Số điện thoại:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['violator_phone'], 0, 1);
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 4: THÔNG TIN PHƯƠNG TIỆN =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Thông tin phương tiện vi phạm:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Biển số xe:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['license_plate'], 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Loại xe:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['vehicle_type'], 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Màu sắc:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['vehicle_color'], 0, 1);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Nhãn hiệu:', 0, 0);
        $this->SetX($contentStart);
        $this->Cell(0, $this->lineHeight, $this->data['vehicle_brand'], 0, 1);
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 5: NỘI DUNG VI PHẠM =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Nội dung vi phạm:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        if (is_array($this->data['violation_type'])) {
            $violation_types = implode(', ', $this->data['violation_type']);
        } else {
            $violation_types = $this->data['violation_type'];
        }
        
        $this->MultiCell(0, $this->lineHeight, 'Hành vi vi phạm: ' . $violation_types, 0, 'L');
        
        if (!empty($this->data['violation_content'])) {
            $this->Ln(1);
            $this->MultiCell(0, $this->lineHeight, 'Chi tiết: ' . $this->data['violation_content'], 0, 'L');
        }
        
        if (!empty($this->data['notes'])) {
            $this->Ln(1);
            $this->MultiCell(0, $this->lineHeight, 'Ghi chú: ' . $this->data['notes'], 0, 'L');
        }
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 6: MỨC XỬ PHẠT =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Mức xử phạt:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        $this->Cell($labelWidth, $this->lineHeight, 'Số tiền phạt:', 0, 0);
        $this->SetX($contentStart);
        $this->SetFont('dejavuserif', 'B', 12);
        $fine_amount = is_numeric($this->data['fine_amount']) ? $this->data['fine_amount'] : 0;
        $this->Cell(0, $this->lineHeight, number_format($fine_amount, 0, ',', '.') . ' VNĐ', 0, 1);
        
        $this->SetFont('dejavuserif', '', 11);
        $this->Cell($labelWidth, $this->lineHeight, 'Bằng chữ:', 0, 0);
        $this->SetX($contentStart);
        $this->MultiCell(0, $this->lineHeight, $this->convertNumberToWords($fine_amount) . ' đồng', 0, 'L');
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 7: Ý KIẾN NGƯỜI VI PHẠM =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Ý kiến của người vi phạm:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        $this->MultiCell(0, $this->lineHeight, 'Đã được thông báo và giải thích về hành vi vi phạm.', 0, 'L');
        $this->Cell(0, $this->lineHeight, 'Người vi phạm đã thừa nhận hành vi vi phạm.', 0, 1);
        $this->Ln($this->sectionSpacing);
        
        // ===== PHẦN 8: BIỆN PHÁP NGĂN CHẶN =====
        $this->SetFont('dejavuserif', 'B', 11);
        $this->Cell(0, $this->lineHeight, 'Các biện pháp ngăn chặn:', 0, 1);
        $this->SetFont('dejavuserif', '', 11);
        
        $this->MultiCell(0, $this->lineHeight, 'Yêu cầu người vi phạm chấm dứt ngay hành vi vi phạm.', 0, 'L');
        $this->Cell(0, $this->lineHeight, 'Lập biên bản xử phạt vi phạm hành chính.', 0, 1);
        $this->Ln(10);
        
        // ===== PHẦN 9: CHỮ KÝ =====
$this->SetFont('dejavuserif', 'B', 11);
$this->Cell(0, $this->lineHeight, 'Biên bản đã được lập xong và giao cho các bên liên quan giữ một bản.', 0, 1, 'C');
$this->Ln(15); // Tăng khoảng cách

// Dòng thời gian và địa điểm lập biên bản
$this->SetFont('dejavuserif', 'I', 11);
$this->Cell(0, $this->lineHeight, 'Hà Nội, ngày ' . $this->data['report_date'] . ' lúc ' . $this->data['report_time'] . ' phút', 0, 1, 'C');
$this->Ln(25); // Tăng khoảng cách đáng kể

// Tiêu đề chữ ký
$this->SetFont('dejavuserif', 'B', 12);
$this->Cell(95, $this->lineHeight + 3, 'NGƯỜI VI PHẠM', 0, 0, 'C');
$this->Cell(95, $this->lineHeight + 3, 'CÁN BỘ LẬP BIÊN BẢN', 0, 1, 'C');
$this->Ln(30); // Khoảng cách rộng cho chữ ký

// Tên với gạch chân
$this->SetFont('dejavuserif', '', 12);
$currentY = $this->GetY();

// Vị trí cho người vi phạm
$x1 = $this->lMargin + 17.5;
$this->SetXY($x1, $currentY);
$this->Cell(60, $this->lineHeight, $this->data['violator_name'], 0, 0, 'C');
$this->SetXY($x1, $currentY + $this->lineHeight + 2);
$this->Cell(60, 0, '', 'T', 0, 'C'); // Gạch chân

// Vị trí cho cán bộ
$x2 = $this->lMargin + 112.5;
$this->SetXY($x2, $currentY);
$this->Cell(60, $this->lineHeight, $this->data['officer_name'], 0, 0, 'C');
$this->SetXY($x2, $currentY + $this->lineHeight + 2);
$this->Cell(60, 0, '', 'T', 1, 'C'); // Gạch chân

$this->Ln(10); // Khoảng cách dưới gạch chân

// Dòng hướng dẫn
$this->SetFont('dejavuserif', 'I', 10);
$this->Cell(95, $this->lineHeight, '(Ký, ghi rõ họ tên)', 0, 0, 'C');
$this->Cell(95, $this->lineHeight, '(Ký, ghi rõ họ tên)', 0, 1, 'C');

// Thêm dòng chức vụ cho cán bộ
$this->Ln(5);
$this->SetFont('dejavuserif', 'I', 9);
$this->Cell(95, $this->lineHeight, '', 0, 0, 'C');
$this->Cell(95, $this->lineHeight, $this->data['officer_position'], 0, 1, 'C');
    }
    
    private function convertNumberToWords($number) {
        $hyphen      = ' ';
        $conjunction = '  ';
        $separator   = ' ';
        $negative    = 'âm ';
        $decimal     = ' phẩy ';
        $dictionary  = array(
            0                   => 'không',
            1                   => 'một',
            2                   => 'hai',
            3                   => 'ba',
            4                   => 'bốn',
            5                   => 'năm',
            6                   => 'sáu',
            7                   => 'bảy',
            8                   => 'tám',
            9                   => 'chín',
            10                  => 'mười',
            11                  => 'mười một',
            12                  => 'mười hai',
            13                  => 'mười ba',
            14                  => 'mười bốn',
            15                  => 'mười lăm',
            16                  => 'mười sáu',
            17                  => 'mười bảy',
            18                  => 'mười tám',
            19                  => 'mười chín',
            20                  => 'hai mươi',
            30                  => 'ba mươi',
            40                  => 'bốn mươi',
            50                  => 'năm mươi',
            60                  => 'sáu mươi',
            70                  => 'bảy mươi',
            80                  => 'tám mươi',
            90                  => 'chín mươi',
            100                 => 'trăm',
            1000                => 'nghìn',
            1000000             => 'triệu',
            1000000000          => 'tỷ',
        );
        
        if (!is_numeric($number)) {
            return 'không đồng';
        }
        
        if ($number < 0) {
            return $negative . $this->convertNumberToWords(abs($number));
        }
        
        $string = $fraction = null;
        
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }
        
        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = floor($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convertNumberToWords($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertNumberToWords($remainder);
                }
                break;
        }
        
        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }
        
        return $string;
    }
}

// ============ CLASS PDF TỪ DB ============
class ViolationPDF extends TCPDF {
    
    function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Cấu hình
        $this->SetCreator('Hệ thống CSGT');
        $this->SetAuthor('Cán bộ CSGT');
        $this->SetTitle('Biên bản vi phạm');
        
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 15);
    }
    
    public function Header() {
        // Quốc hiệu
        $this->SetFont('dejavuserif', 'B', 12);
        $this->Cell(0, 10, 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 0, 1, 'C');
        $this->SetFont('dejavuserif', 'B', 10);
        $this->Cell(0, 10, 'Độc lập - Tự do - Hạnh phúc', 0, 1, 'C');
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavuserif', 'I', 8);
        $this->Cell(0, 10, 'Trang ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
    
    function AddSectionTitle($title) {
        $this->SetFont('dejavuserif', 'B', 12);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->Ln(3);
    }
}

// ============ MAIN CONTROLLER CLASS ============
class OfficerController {
    private $db;
    
    public function __construct() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/config/database.php';
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['officer_id'])) {
            header('Location: /traffic/app/views/officers/login.php');
            exit();
        }
    }
    
    // Dashboard cán bộ
    public function dashboard() {
        $this->checkAuth();
        
        // Lấy thống kê
        $stats = $this->getDashboardStats();
        
        include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/dashboard.php';
    }
    
    // ============ CHỨC NĂNG LẬP BIÊN BẢN THỦ CÔNG ============
    
    // Form lập biên bản thủ công
    public function createManualViolation() {
        $this->checkAuth();
        
        // Kiểm tra session data
        if (!isset($_SESSION['ho_ten'])) {
            $_SESSION['ho_ten'] = 'Cán bộ CSGT';
        }
        if (!isset($_SESSION['ma_can_bo'])) {
            $_SESSION['ma_can_bo'] = 'CB000';
        }
        if (!isset($_SESSION['don_vi'])) {
            $_SESSION['don_vi'] = 'Đội CSGT Quận Ba Đình';
        }
        if (!isset($_SESSION['cap_bac'])) {
            $_SESSION['cap_bac'] = 'Trung sĩ';
        }
        
        // Hiển thị form trực tiếp
        $this->showManualViolationForm();
    }
    

    public function createFromCamera() {
    $this->checkAuth();
    
    if (!isset($_SESSION['camera_violation_data'])) {
        $_SESSION['error'] = "Không có dữ liệu vi phạm từ camera";
        header('Location: /traffic/app/controllers/CameraController.php?action=index');
        exit();
    }
    
    $cameraData = $_SESSION['camera_violation_data'];
    
    // Tạo dữ liệu cho form biên bản
    $data = [
        'violator_name' => 'Chưa xác định', // Cán bộ sẽ nhập thêm
        'license_plate' => $cameraData['license_plate'] ?? '',
        'vehicle_type' => $cameraData['vehicle_type'] ?? '',
        'vehicle_color' => $cameraData['vehicle_color'] ?? '',
        'violation_type' => [$cameraData['violation_type'] ?? ''],
        'violation_content' => "Vi phạm phát hiện từ camera: " . $cameraData['description'] ?? '',
        'violation_location' => "Camera " . $this->getCameraName($cameraData['camera_id']),
        'violation_time' => $cameraData['violation_time'] ?? date('H:i'),
        'violation_date' => date('Y-m-d'),
        'fine_amount' => $this->calculateFineAmount($cameraData['violation_type'] ?? ''),
        'camera_data' => $cameraData
    ];
    
    // Hiển thị form biên bản với dữ liệu từ camera
    $this->showCameraViolationForm($data);
}

private function showCameraViolationForm($data) {
    include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/camera_violation_form.php';
}

private function getCameraName($cameraId) {
    $cameras = [
        1 => 'Nguyễn Tất Thành',
        2 => 'Điện Biên Phủ'
    ];
    return $cameras[$cameraId] ?? 'Không xác định';
}

private function calculateFineAmount($violationType) {
    $fines = [
        'Vượt đèn đỏ' => 800000,
        'Không đội mũ bảo hiểm' => 200000,
        'Quá tốc độ' => 1500000,
        'Đi sai làn đường' => 300000,
        'Dừng đỗ sai quy định' => 500000,
        'Không có GPLX' => 1000000,
        'Sử dụng điện thoại' => 800000
    ];
    return $fines[$violationType] ?? 200000;
}


// Xử lý biên bản từ camera
// Xử lý biên bản từ camera
public function processCameraViolation() {
    $this->checkAuth();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /traffic/app/controllers/CameraController.php?action=index');
        exit();
    }
    
    if (!isset($_SESSION['camera_violation_data'])) {
        $_SESSION['error'] = "Không có dữ liệu từ camera";
        header('Location: /traffic/app/controllers/CameraController.php?action=index');
        exit();
    }
    
    $cameraData = $_SESSION['camera_violation_data'];
    
    // Lấy dữ liệu từ form
    $formData = [
        'violator_name' => $_POST['violator_name'] ?? '',
        'violator_id' => $_POST['violator_id'] ?? '',
        'violator_phone' => $_POST['violator_phone'] ?? '',
        'violator_address' => $_POST['violator_address'] ?? '',
        'license_plate' => $cameraData['license_plate'],
        'vehicle_type' => $cameraData['vehicle_type'],
        'vehicle_color' => $cameraData['vehicle_color'] ?? '',
        'violation_type' => [$cameraData['violation_type']],
        'violation_date' => $cameraData['violation_date'] ?? date('Y-m-d'),
        'violation_time' => $cameraData['violation_time'] ?? date('H:i'),
        'violation_location' => 'Camera ' . ($cameraData['camera_id'] == 1 ? 'Nguyễn Tất Thành' : 'Điện Biên Phủ'),
        'violation_content' => $_POST['violation_content'] ?? '',
        'fine_amount' => $cameraData['fine_amount'] ?? 0,
        'legal_basis' => 'Nghị định 100/2019/NĐ-CP',
        'notes' => 'Vi phạm được phát hiện qua hệ thống camera giám sát.',
        'officer_name' => $_SESSION['officer_name'] ?? 'Cán bộ',
        'officer_unit' => $_SESSION['officer_unit'] ?? 'Đơn vị',
        'officer_position' => $_SESSION['officer_rank'] ?? 'Cán bộ'
    ];
    
    // Tạo số biên bản
    $formData['report_number'] = 'CAM-' . date('Ymd') . '-' . rand(1000, 9999);
    $formData['report_date'] = date('d/m/Y');
    $formData['report_time'] = date('H:i');
    
    // Tạo PDF (giả sử có phương thức generateManualViolationPDF)
    $this->generateManualViolationPDF($formData);
    
    // Xóa dữ liệu camera khỏi session
    unset($_SESSION['camera_violation_data']);
}

    // Hiển thị form lập biên bản thủ công (giữ nguyên HTML form từ code cũ)
    private function showManualViolationForm() {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lập Biên Bản Thủ Công - CSGT</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .header {
                background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                color: white;
            }
            .violation-card {
                border-left: 5px solid #3b82f6;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                margin-bottom: 20px;
                transition: all 0.3s;
            }
            .violation-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0,0,0,0.15);
            }
            .form-section {
                background: white;
                border-radius: 10px;
                padding: 25px;
                margin-bottom: 25px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .required:after {
                content: " *";
                color: red;
            }
            .btn-primary {
                background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
                border: none;
                padding: 12px 30px;
            }
        </style>
    </head>
    <body>
        <!-- Header -->
        <div class="header py-3 mb-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0"><i class="fas fa-file-pen me-2"></i>Lập Biên Bản Thủ Công</h2>
                        <p class="mb-0">Nhập thông tin và xuất biên bản ngay lập tức</p>
                    </div>
                    <div class="text-end">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($_SESSION['officer_name'] ?? 'Cán bộ'); ?></strong></p>
                        <p class="mb-0"><?php echo htmlspecialchars($_SESSION['officer_unit'] ?? 'Đơn vị'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Thông báo lỗi/nhắc nhở -->
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form chính -->
            <form action="/traffic/app/controllers/OfficerController.php?action=processManualViolation" 
                  method="POST" id="violationForm">
                
                <!-- PHẦN 1: THÔNG TIN NGƯỜI VI PHẠM -->
                <div class="form-section">
                    <h4 class="mb-4 text-primary">
                        <i class="fas fa-user me-2"></i>Thông Tin Người Vi Phạm
                    </h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Họ và tên</label>
                            <input type="text" class="form-control" name="violator_name" 
                                   placeholder="Nguyễn Văn A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Số CMND/CCCD</label>
                            <input type="text" class="form-control" name="violator_id" 
                                   placeholder="00123456789" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" name="violator_birthday">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quốc tịch</label>
                            <select class="form-select" name="violator_nationality">
                                <option value="Việt Nam" selected>Việt Nam</option>
                                <option value="Nước ngoài">Nước ngoài</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Số điện thoại</label>
                            <input type="tel" class="form-control" name="violator_phone" 
                                   placeholder="0912 345 678" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" name="violator_address" 
                                   placeholder="Số nhà, đường, phường, quận, thành phố">
                        </div>
                    </div>
                </div>

                <!-- PHẦN 2: THÔNG TIN PHƯƠNG TIỆN -->
                <div class="form-section">
                    <h4 class="mb-4 text-primary">
                        <i class="fas fa-car me-2"></i>Thông Tin Phương Tiện
                    </h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Biển số xe</label>
                            <input type="text" class="form-control" name="license_plate" 
                                   placeholder="29A1-12345" required style="text-transform:uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Loại xe</label>
                            <select class="form-select" name="vehicle_type" required>
                                <option value="">-- Chọn loại xe --</option>
                                <option value="Ô tô">Ô tô</option>
                                <option value="Xe máy">Xe máy</option>
                                <option value="Xe đạp">Xe đạp</option>
                                <option value="Xe tải">Xe tải</option>
                                <option value="Xe khách">Xe khách</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Màu sắc</label>
                            <input type="text" class="form-control" name="vehicle_color" 
                                   placeholder="Đen, trắng, đỏ...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nhãn hiệu</label>
                            <input type="text" class="form-control" name="vehicle_brand" 
                                   placeholder="Honda, Yamaha, Toyota...">
                        </div>
                    </div>
                </div>

                <!-- PHẦN 3: THÔNG TIN VI PHẠM -->
                <div class="form-section">
                    <h4 class="mb-4 text-primary">
                        <i class="fas fa-exclamation-triangle me-2"></i>Thông Tin Vi Phạm
                    </h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Ngày vi phạm</label>
                            <input type="date" class="form-control" name="violation_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Giờ vi phạm</label>
                            <input type="time" class="form-control" name="violation_time" 
                                   value="<?php echo date('H:i'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Địa điểm vi phạm</label>
                            <input type="text" class="form-control" name="violation_location" 
                                   placeholder="Số, đường, quận/huyện, tỉnh/thành phố" required>
                        </div>
                        
                        <!-- Loại vi phạm -->
                        <div class="col-12">
                            <label class="form-label required">Loại vi phạm</label>
                            <div class="border rounded p-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Vượt đèn đỏ" id="vp1">
                                            <label class="form-check-label" for="vp1">
                                                Vượt đèn đỏ
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Không đội MBH" id="vp2">
                                            <label class="form-check-label" for="vp2">
                                                Không đội mũ bảo hiểm
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Quá tốc độ" id="vp3">
                                            <label class="form-check-label" for="vp3">
                                                Quá tốc độ cho phép
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Đi sai làn" id="vp4">
                                            <label class="form-check-label" for="vp4">
                                                Đi sai làn đường
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Đậu sai quy định" id="vp5">
                                            <label class="form-check-label" for="vp5">
                                                Đậu đỗ sai quy định
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input violation-type" type="checkbox" 
                                                   name="violation_type[]" value="Không GPLX" id="vp6">
                                            <label class="form-check-label" for="vp6">
                                                Không có giấy phép lái xe
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Mô tả chi tiết</label>
                            <textarea class="form-control" name="violation_content" rows="3" 
                                      placeholder="Mô tả chi tiết hành vi vi phạm..."></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label required">Mức phạt (VNĐ)</label>
                            <input type="number" class="form-control" name="fine_amount" 
                                   min="100000" step="100000" value="200000" required>
                            <small class="text-muted">Tự động tính theo loại vi phạm đã chọn</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Căn cứ pháp lý</label>
                            <select class="form-select" name="legal_basis">
                                <option value="Nghị định 100/2019/NĐ-CP" selected>Nghị định 100/2019/NĐ-CP</option>
                                <option value="Nghị định 46/2016/NĐ-CP">Nghị định 46/2016/NĐ-CP</option>
                                <option value="Luật Giao thông đường bộ 2008">Luật Giao thông đường bộ 2008</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Ghi chú thêm</label>
                            <textarea class="form-control" name="notes" rows="2" 
                                      placeholder="Ghi chú thêm (nếu có)..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Các trường ẩn -->
                <input type="hidden" name="officer_name" value="<?php echo htmlspecialchars($_SESSION['officer_name'] ?? ''); ?>">
                <input type="hidden" name="officer_unit" value="<?php echo htmlspecialchars($_SESSION['officer_unit'] ?? ''); ?>">

                <!-- Nút hành động -->
                <div class="form-section text-center">
                    <button type="submit" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-file-pdf me-2"></i>Xuất PDF Ngay
                    </button>
                    <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" 
                       class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Quay Lại
                    </a>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-5 pt-4 border-top text-center text-muted">
                <p>Hệ thống lập biên bản thủ công • CSGT v1.0 • <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Tự động tính mức phạt khi chọn loại vi phạm
            document.querySelectorAll('.violation-type').forEach(checkbox => {
                checkbox.addEventListener('change', calculateFineAmount);
            });

            function calculateFineAmount() {
                const finePrices = {
                    'Vượt đèn đỏ': 800000,
                    'Không đội MBH': 200000,
                    'Quá tốc độ': 1500000,
                    'Đi sai làn': 300000,
                    'Đậu sai quy định': 500000,
                    'Không GPLX': 1000000
                };

                let total = 0;
                document.querySelectorAll('.violation-type:checked').forEach(checkbox => {
                    total += finePrices[checkbox.value] || 0;
                });

                // Đảm bảo mức phạt tối thiểu
                if (total === 0) total = 200000;

                document.querySelector('input[name="fine_amount"]').value = total;
            }

            // Khởi tạo
            document.addEventListener('DOMContentLoaded', calculateFineAmount);

            // Xử lý submit form
            document.getElementById('violationForm').addEventListener('submit', function(e) {
                // Kiểm tra ít nhất một loại vi phạm được chọn
                const violationTypes = document.querySelectorAll('.violation-type:checked');
                if (violationTypes.length === 0) {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất một loại vi phạm!');
                    return false;
                }
            });
        </script>
    </body>
    </html>
    <?php
}
    
    // Xử lý tạo biên bản thủ công và xuất PDF ngay
    public function processManualViolation() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /traffic/app/controllers/OfficerController.php?action=createManualViolation');
            exit();
        }
        
        // Bật output buffering để không có output nào trước PDF
        ob_start();
        
        // Lấy dữ liệu từ form
        $data = $this->getManualFormData();
        
        // Validate
        $errors = $this->validateManualViolationData($data);
        
        if (!empty($errors)) {
            ob_end_clean();
            $_SESSION['error'] = implode("<br>", $errors);
            header('Location: /traffic/app/controllers/OfficerController.php?action=createManualViolation');
            exit();
        }
        
        // Tạo PDF ngay lập tức
        $this->generateManualViolationPDF($data);
    }
    
    // Lấy dữ liệu từ form thủ công
    private function getManualFormData() {
        return [
            // Thông tin cán bộ
            'officer_name' => $_SESSION['ho_ten'] ?? 'Cán bộ CSGT',
            'officer_code' => $_SESSION['ma_can_bo'] ?? 'CB000',
            'officer_unit' => $_SESSION['don_vi'] ?? 'Đơn vị',
            'officer_position' => $_SESSION['cap_bac'] ?? 'Cán bộ',
            
            // Thông tin người vi phạm
            'violator_name' => $_POST['violator_name'] ?? '',
            'violator_id' => $_POST['violator_id'] ?? '',
            'violator_phone' => $_POST['violator_phone'] ?? '',
            'violator_address' => $_POST['violator_address'] ?? '',
            'violator_birthday' => $_POST['violator_birthday'] ?? '',
            'violator_nationality' => $_POST['violator_nationality'] ?? 'Việt Nam',
            
            // Thông tin phương tiện
            'license_plate' => $_POST['license_plate'] ?? '',
            'vehicle_type' => $_POST['vehicle_type'] ?? '',
            'vehicle_color' => $_POST['vehicle_color'] ?? '',
            'vehicle_brand' => $_POST['vehicle_brand'] ?? '',
            
            // Thông tin vi phạm
            'violation_date' => $_POST['violation_date'] ?? date('Y-m-d'),
            'violation_time' => $_POST['violation_time'] ?? date('H:i'),
            'violation_location' => $_POST['violation_location'] ?? '',
            'violation_type' => isset($_POST['violation_type']) ? 
                               (is_array($_POST['violation_type']) ? $_POST['violation_type'] : [$_POST['violation_type']]) : [],
            'violation_content' => $_POST['violation_content'] ?? '',
            'fine_amount' => $_POST['fine_amount'] ?? 0,
            'notes' => $_POST['notes'] ?? '',
            
            // Căn cứ pháp lý
            'legal_basis' => $_POST['legal_basis'] ?? 'Nghị định 100/2019/NĐ-CP',
            
            // Thông tin biên bản
            'report_date' => date('d/m/Y'),
            'report_time' => date('H:i'),
            'report_number' => $this->generateReportNumber()
        ];
    }
    
    // Validate dữ liệu thủ công
    private function validateManualViolationData($data) {
        $errors = [];
        
        if (empty($data['violator_name'])) {
            $errors[] = "Họ tên người vi phạm không được để trống";
        }
        
        if (empty($data['violator_id'])) {
            $errors[] = "Số CMND/CCCD không được để trống";
        }
        
        if (empty($data['license_plate'])) {
            $errors[] = "Biển số xe không được để trống";
        }
        
        if (empty($data['violation_location'])) {
            $errors[] = "Địa điểm vi phạm không được để trống";
        }
        
        if (empty($data['violation_type'])) {
            $errors[] = "Vui lòng chọn loại vi phạm";
        }
        
        if (empty($data['fine_amount']) || $data['fine_amount'] <= 0) {
            $errors[] = "Mức phạt không hợp lệ";
        }
        
        return $errors;
    }
    
    // Tạo số biên bản
    private function generateReportNumber() {
        $prefix = 'BB';
        $date = date('Ymd');
        $random = rand(1000, 9999);
        return $prefix . $date . $random;
    }
    
    // Tạo PDF biên bản thủ công
    // Tạo PDF biên bản thủ công
private function generateManualViolationPDF($data) {
    // Tạo và xuất PDF
    ob_end_clean(); // Xóa hết output buffer trước khi xuất PDF
    
    $pdf = new ManualViolationPDF($data);
    
    // Sử dụng cách xuất an toàn hơn
    $filename = 'Bien_ban_' . $data['report_number'] . '.pdf';
    
    // Cách 1: Xuất trực tiếp ra trình duyệt (In-line)
    $pdf->Output($filename, 'I');
    
    // Cách 2: Hoặc nếu cách 1 không hoạt động, thử:
    // $pdf->Output($filename, 'D'); // Download
    // $pdf->Output($filename, 'S'); // Trả về chuỗi
    
    exit;
}
    
    // ============ CHỨC NĂNG LẬP BIÊN BẢN LƯU VÀO DB ============
    
    // Lập biên bản (lưu DB)
    public function addViolation() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddViolation();
        } else {
            include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/add_violation.php';
        }
    }
    
    // Xử lý tạo biên bản (lưu DB)
    private function handleAddViolation() {
        // Lấy dữ liệu từ form
        $data = [
            'bien_so' => $_POST['bien_so'] ?? '',
            'loai_xe' => $_POST['loai_xe'] ?? '',
            'ho_ten_nguoi_vp' => $_POST['ho_ten_nguoi_vp'] ?? '',
            'cccd' => $_POST['cccd'] ?? '',
            'sdt_nguoi_vp' => $_POST['sdt_nguoi_vp'] ?? '',
            'dia_chi' => $_POST['dia_chi'] ?? '',
            'dia_diem' => $_POST['dia_diem'] ?? '',
            'thoi_gian' => $_POST['thoi_gian'] ?? '',
            'mo_ta' => $_POST['mo_ta'] ?? '',
            'loi_vi_pham' => $_POST['loi_vi_pham'] ?? []
        ];
        
        // Validate
        $errors = $this->validateViolationData($data);
        
        if (empty($errors)) {
            if ($this->createViolation($data)) {
                $_SESSION['success'] = "Biên bản đã được tạo thành công!";
                header('Location: /traffic/app/controllers/OfficerController.php?action=dashboard');
                exit();
            } else {
                $_SESSION['error'] = "Lỗi khi lưu biên bản!";
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
        
        // Quay lại form với dữ liệu cũ
        $_SESSION['form_data'] = $data;
        header('Location: /traffic/app/views/officers/add_violation.php');
        exit();
    }
    
    // Validate dữ liệu biên bản (DB)
    private function validateViolationData($data) {
        $errors = [];
        
        if (empty($data['bien_so'])) {
            $errors[] = "Biển số xe không được để trống";
        }
        
        if (empty($data['loi_vi_pham'])) {
            $errors[] = "Vui lòng chọn ít nhất một lỗi vi phạm";
        }
        
        if (empty($data['dia_diem'])) {
            $errors[] = "Địa điểm vi phạm không được để trống";
        }
        
        if (empty($data['thoi_gian'])) {
            $errors[] = "Thời gian vi phạm không được để trống";
        }
        
        return $errors;
    }
    
    // Tạo biên bản trong database
    private function createViolation($data) {
        try {
            // Tạo mã vi phạm mới
            $last_vp = $this->getLastViolationCode();
            $new_number = intval(substr($last_vp, 10)) + 1;
            $ma_vi_pham = 'VP' . date('Y') . str_pad($new_number, 6, '0', STR_PAD_LEFT);
            
            // Tính tổng tiền phạt
            $tong_tien = 0;
            $loi_descriptions = [];
            
            $loiPrices = [
                'VP001' => 800000,
                'VP002' => 200000,
                'VP003' => 1500000,
                'VP004' => 300000,
                'VP005' => 1000000,
                'VP006' => 500000
            ];
            
            foreach ($data['loi_vi_pham'] as $ma_loi) {
                $tong_tien += $loiPrices[$ma_loi] ?? 0;
                $loi_descriptions[] = $this->getViolationName($ma_loi);
            }
            
            // Tạo vehicle_id tạm thời
            $vehicle_id = rand(1000, 9999);
            
            // Lưu vào violations table
            $sql = "INSERT INTO violations (
                ma_vi_pham, bien_so, vehicle_id, violation_type_id,
                thoi_gian_vi_pham, dia_diem, muc_phat, trang_thai,
                ghi_chu, nguoi_lap_bien_ban, created_at
            ) VALUES (
                :ma_vi_pham, :bien_so, :vehicle_id, :violation_type_id,
                :thoi_gian_vi_pham, :dia_diem, :muc_phat, 'Chưa xử lý',
                :ghi_chu, :nguoi_lap_bien_ban, NOW()
            )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':ma_vi_pham' => $ma_vi_pham,
                ':bien_so' => $data['bien_so'],
                ':vehicle_id' => $vehicle_id,
                ':violation_type_id' => 1,
                ':thoi_gian_vi_pham' => $data['thoi_gian'],
                ':dia_diem' => $data['dia_diem'],
                ':muc_phat' => $tong_tien,
                ':ghi_chu' => $this->buildGhiChu($data, $loi_descriptions),
                ':nguoi_lap_bien_ban' => $_SESSION['officer_id']
            ]);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Lỗi tạo biên bản: " . $e->getMessage());
            return false;
        }
    }
    
    // Lấy mã vi phạm cuối cùng
    private function getLastViolationCode() {
        $sql = "SELECT ma_vi_pham FROM violations ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['ma_vi_pham'] : 'VP' . date('Y') . '000000';
    }
    
    // Xây dựng ghi chú từ dữ liệu
    private function buildGhiChu($data, $loi_descriptions) {
        $ghi_chu = "Họ tên: " . $data['ho_ten_nguoi_vp'] . "\n";
        $ghi_chu .= "CCCD: " . $data['cccd'] . "\n";
        $ghi_chu .= "SĐT: " . $data['sdt_nguoi_vp'] . "\n";
        $ghi_chu .= "Địa chỉ: " . $data['dia_chi'] . "\n";
        $ghi_chu .= "Loại xe: " . $data['loai_xe'] . "\n";
        $ghi_chu .= "Lỗi vi phạm: " . implode(", ", $loi_descriptions) . "\n";
        $ghi_chu .= "Mô tả: " . $data['mo_ta'];
        
        return $ghi_chu;
    }
    
    // Lấy tên lỗi vi phạm
    private function getViolationName($ma_loi) {
        $loiNames = [
            'VP001' => 'Vượt đèn đỏ',
            'VP002' => 'Không đội mũ bảo hiểm',
            'VP003' => 'Điều khiển xe quá tốc độ',
            'VP004' => 'Dừng đỗ sai quy định',
            'VP005' => 'Không có giấy phép lái xe',
            'VP006' => 'Sử dụng điện thoại khi lái xe'
        ];
        
        return $loiNames[$ma_loi] ?? 'Lỗi không xác định';
    }
    
    // ============ CHỨC NĂNG XUẤT PDF TỪ DB ============
    
    // Tạo PDF từ biên bản trong DB
    public function exportPDF() {
        $this->checkAuth();
        
        if (!isset($_GET['id'])) {
            die("Không tìm thấy biên bản");
        }
        
        $id = intval($_GET['id']);
        
        // Bật output buffering
        ob_start();
        
        // Lấy thông tin biên bản từ bảng violations
        $sql = "SELECT v.*, o.ho_ten as officer_name 
                FROM violations v 
                LEFT JOIN officers o ON v.nguoi_lap_bien_ban = o.id 
                WHERE v.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$violation) {
            ob_end_clean();
            die("Không tìm thấy biên bản");
        }
        
        // Kiểm tra quyền
        if ($violation['nguoi_lap_bien_ban'] != $_SESSION['officer_id']) {
            ob_end_clean();
            die("Bạn không có quyền xem biên bản này");
        }
        
        // Phân tích ghi chú để lấy thông tin
        $violator_info = $this->parseGhiChu($violation['ghi_chu']);
        
        // Tạo PDF với TCPDF
        ob_end_clean(); // Xóa output buffer
        
        $pdf = new ViolationPDF();
        $pdf->AddPage();
        $pdf->SetFont('dejavuserif', 'B', 16);
        $pdf->Cell(0, 10, 'BIÊN BẢN VI PHẠM HÀNH CHÍNH', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Số biên bản
        $pdf->SetFont('dejavuserif', '', 12);
        $pdf->Cell(40, 10, 'Số:', 0, 0);
        $pdf->SetFont('dejavuserif', 'B', 12);
        $pdf->Cell(0, 10, $violation['ma_vi_pham'], 0, 1);
        $pdf->Ln(5);
        
        // Thông tin cơ bản
        $pdf->AddSectionTitle('1. THÔNG TIN CƠ BẢN');
        
        $pdf->SetFont('dejavuserif', '', 11);
        $thoi_gian = !empty($violation['thoi_gian_vi_pham']) ? $violation['thoi_gian_vi_pham'] : date('Y-m-d H:i:s');
        $pdf->Cell(50, 8, 'Hồi:', 0, 0);
        $pdf->Cell(0, 8, date('H:i', strtotime($thoi_gian)) . ' phút', 0, 1);
        
        $pdf->Cell(50, 8, 'Ngày:', 0, 0);
        $pdf->Cell(0, 8, date('d/m/Y', strtotime($thoi_gian)), 0, 1);
        
        $pdf->Cell(50, 8, 'Tại:', 0, 0);
        $pdf->Cell(0, 8, $violation['dia_diem'], 0, 1);
        $pdf->Ln(5);
        
        // Thông tin người lập biên bản
        $pdf->AddSectionTitle('2. NGƯỜI LẬP BIÊN BẢN');
        
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->Cell(60, 8, 'Họ và tên:', 0, 0);
        $pdf->Cell(0, 8, $violation['officer_name'] ?? $_SESSION['ho_ten'], 0, 1);
        
        $pdf->Cell(60, 8, 'Mã cán bộ:', 0, 0);
        $pdf->Cell(0, 8, $_SESSION['ma_can_bo'], 0, 1);
        
        $pdf->Cell(60, 8, 'Đơn vị công tác:', 0, 0);
        $pdf->Cell(0, 8, $_SESSION['don_vi'], 0, 1);
        $pdf->Ln(5);
        
        // Thông tin người vi phạm
        $pdf->AddSectionTitle('3. THÔNG TIN NGƯỜI VI PHẠM');
        
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->Cell(60, 8, 'Họ và tên:', 0, 0);
        $pdf->Cell(0, 8, $violator_info['ho_ten'] ?? '', 0, 1);
        
        $pdf->Cell(60, 8, 'Số CCCD/CMND:', 0, 0);
        $pdf->Cell(0, 8, $violator_info['cccd'] ?? '', 0, 1);
        
        $pdf->Cell(60, 8, 'Số điện thoại:', 0, 0);
        $pdf->Cell(0, 8, $violator_info['sdt'] ?? '', 0, 1);
        
        $pdf->Cell(60, 8, 'Địa chỉ:', 0, 0);
        $pdf->Cell(0, 8, $violator_info['dia_chi'] ?? '', 0, 1);
        $pdf->Ln(5);
        
        // Thông tin phương tiện
        $pdf->AddSectionTitle('4. THÔNG TIN PHƯƠNG TIỆN');
        
        $pdf->Cell(60, 8, 'Biển số xe:', 0, 0);
        $pdf->Cell(0, 8, $violation['bien_so'], 0, 1);
        
        $pdf->Cell(60, 8, 'Loại phương tiện:', 0, 0);
        $pdf->Cell(0, 8, $violator_info['loai_xe'] ?? '', 0, 1);
        $pdf->Ln(5);
        
        // Nội dung vi phạm
        $pdf->AddSectionTitle('5. NỘI DUNG VI PHẠM');
        
        $pdf->MultiCell(0, 8, 'Loại lỗi: ' . ($violator_info['loi_vi_pham'] ?? ''));
        $pdf->Ln(3);
        
        // Mô tả chi tiết
        $pdf->SetFont('dejavuserif', 'B', 11);
        $pdf->Cell(0, 8, 'Mô tả chi tiết:', 0, 1);
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->MultiCell(0, 8, $violator_info['mo_ta'] ?? '');
        $pdf->Ln(5);
        
        // Mức xử phạt
        $pdf->AddSectionTitle('6. MỨC XỬ PHẠT');
        
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->Cell(60, 8, 'Tổng số tiền:', 0, 0);
        $pdf->SetFont('dejavuserif', 'B', 12);
        $muc_phat = is_numeric($violation['muc_phat']) ? $violation['muc_phat'] : 0;
        $pdf->Cell(0, 8, number_format($muc_phat, 0, ',', '.') . ' VNĐ', 0, 1);
        
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->Cell(60, 8, 'Trạng thái:', 0, 0);
        $pdf->Cell(0, 8, $violation['trang_thai'], 0, 1);
        $pdf->Ln(10);
        
        // Chữ ký
        $pdf->SetFont('dejavuserif', 'I', 11);
        $pdf->Cell(0, 8, 'Hà Nội, ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y'), 0, 1, 'C');
        $pdf->Ln(15);
        
        $pdf->SetFont('dejavuserif', 'B', 11);
        $pdf->Cell(95, 8, 'NGƯỜI VI PHẠM', 0, 0, 'C');
        $pdf->Cell(95, 8, 'CÁN BỘ LẬP BIÊN BẢN', 0, 1, 'C');
        $pdf->Ln(20);
        
        $pdf->SetFont('dejavuserif', '', 11);
        $pdf->Cell(95, 8, $violator_info['ho_ten'] ?? '', 0, 0, 'C');
        $pdf->Cell(95, 8, $violation['officer_name'] ?? $_SESSION['ho_ten'], 0, 1, 'C');
        
        // Xuất file
        $pdf->Output('I', 'Bien_ban_' . $violation['ma_vi_pham'] . '.pdf');
        exit;
    }
    
    // Phân tích ghi chú để lấy thông tin
    private function parseGhiChu($ghi_chu) {
        $info = [];
        $lines = explode("\n", $ghi_chu);
        
        foreach ($lines as $line) {
            if (strpos($line, 'Họ tên:') !== false) {
                $info['ho_ten'] = trim(str_replace('Họ tên:', '', $line));
            } elseif (strpos($line, 'CCCD:') !== false) {
                $info['cccd'] = trim(str_replace('CCCD:', '', $line));
            } elseif (strpos($line, 'SĐT:') !== false) {
                $info['sdt'] = trim(str_replace('SĐT:', '', $line));
            } elseif (strpos($line, 'Địa chỉ:') !== false) {
                $info['dia_chi'] = trim(str_replace('Địa chỉ:', '', $line));
            } elseif (strpos($line, 'Loại xe:') !== false) {
                $info['loai_xe'] = trim(str_replace('Loại xe:', '', $line));
            } elseif (strpos($line, 'Lỗi vi phạm:') !== false) {
                $info['loi_vi_pham'] = trim(str_replace('Lỗi vi phạm:', '', $line));
            } elseif (strpos($line, 'Mô tả:') !== false) {
                $info['mo_ta'] = trim(str_replace('Mô tả:', '', $line));
            }
        }
        
        return $info;
    }
    
    // ============ CHỨC NĂNG DANH SÁCH VI PHẠM ============
    
    // Danh sách vi phạm đã tạo
    public function violationsList() {
        $this->checkAuth();
        
        $page = $_GET['page'] ?? 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Lấy vi phạm của cán bộ này
        $violations = $this->getOfficerViolations($_SESSION['officer_id'], $limit, $offset);
        $total = $this->countOfficerViolations($_SESSION['officer_id']);
        $totalPages = ceil($total / $limit);
        
        include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/violations_list.php';
    }
    
    // Xem chi tiết vi phạm
    public function viewViolation($id) {
        $this->checkAuth();
        
        $sql = "SELECT * FROM violations WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $violation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$violation) {
            $_SESSION['error'] = "Không tìm thấy biên bản!";
            header('Location: /traffic/app/controllers/OfficerController.php?action=violationsList');
            exit();
        }
        
        // Kiểm tra quyền xem
        if ($violation['nguoi_lap_bien_ban'] != $_SESSION['officer_id']) {
            $_SESSION['error'] = "Bạn không có quyền xem biên bản này!";
            header('Location: /traffic/app/controllers/OfficerController.php?action=violationsList');
            exit();
        }
        
        // Phân tích ghi chú
        $violator_info = $this->parseGhiChu($violation['ghi_chu']);
        $violation = array_merge($violation, $violator_info);
        
        include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/view_violation.php';
    }
    
    // Cập nhật trạng thái vi phạm
    public function updateViolationStatus() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            // Kiểm tra vi phạm tồn tại
            $sql = "SELECT * FROM violations WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $violation = $stmt->fetch();
            
            if ($violation && $violation['nguoi_lap_bien_ban'] == $_SESSION['officer_id']) {
                $sql = "UPDATE violations SET trang_thai = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                if ($stmt->execute([$status, $id])) {
                    $_SESSION['success'] = "Cập nhật trạng thái thành công!";
                } else {
                    $_SESSION['error'] = "Lỗi khi cập nhật trạng thái!";
                }
            } else {
                $_SESSION['error'] = "Không tìm thấy biên bản hoặc không có quyền!";
            }
        }
        
        header('Location: /traffic/app/controllers/OfficerController.php?action=violationsList');
        exit();
    }
    
    // Tra cứu vi phạm theo biển số
    public function searchViolation() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $bien_so = $_POST['bien_so'] ?? '';
            
            // Tra cứu
            $sql = "SELECT * FROM violations WHERE bien_so LIKE ? ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['%' . $bien_so . '%']);
            $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/search_results.php';
        } else {
            include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/search_violation.php';
        }
    }
    
    // Nộp phạt thủ công
    public function manualPayment() {
        $this->checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $violationIds = $_POST['violation_ids'] ?? [];
            
            if (empty($violationIds)) {
                $_SESSION['error'] = "Vui lòng chọn vi phạm để nộp phạt!";
                header('Location: /traffic/app/controllers/OfficerController.php?action=violationsList');
                exit();
            }
            
            // Cập nhật trạng thái đã thanh toán
            $placeholders = str_repeat('?,', count($violationIds) - 1) . '?';
            $sql = "UPDATE violations SET trang_thai = 'Đã thanh toán' WHERE id IN ($placeholders) AND nguoi_lap_bien_ban = ?";
            $params = array_merge($violationIds, [$_SESSION['officer_id']]);
            
            $stmt = $this->db->prepare($sql);
            if ($stmt->execute($params)) {
                $_SESSION['success'] = "Đã cập nhật trạng thái thanh toán cho " . count($violationIds) . " vi phạm!";
            } else {
                $_SESSION['error'] = "Lỗi khi cập nhật trạng thái thanh toán!";
            }
            
            header('Location: /traffic/app/controllers/OfficerController.php?action=violationsList');
            exit();
        }
        
        include $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/officers/manual_payment.php';
    }
    
    // ============ CÁC HÀM HELPER ============
    
    // Lấy thống kê dashboard
    private function getDashboardStats() {
        $officerId = $_SESSION['officer_id'];
        
        $stats = [
            'total_violations' => $this->countOfficerViolations($officerId),
            'pending_violations' => $this->countOfficerViolationsByStatus($officerId, 'Chưa xử lý'),
            'completed_violations' => $this->countOfficerViolationsByStatus($officerId, 'Đã thanh toán'),
            'total_amount' => $this->getTotalViolationAmount($officerId)
        ];
        
        return $stats;
    }
    
    private function getOfficerViolations($officerId, $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM violations 
                WHERE nguoi_lap_bien_ban = :officer_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':officer_id', $officerId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function countOfficerViolations($officerId) {
        $sql = "SELECT COUNT(*) FROM violations WHERE nguoi_lap_bien_ban = :officer_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':officer_id' => $officerId]);
        return $stmt->fetchColumn();
    }
    
    private function countOfficerViolationsByStatus($officerId, $status) {
        $sql = "SELECT COUNT(*) FROM violations 
                WHERE nguoi_lap_bien_ban = :officer_id AND trang_thai = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':officer_id' => $officerId, ':status' => $status]);
        return $stmt->fetchColumn();
    }
    
    private function getTotalViolationAmount($officerId) {
        $sql = "SELECT SUM(muc_phat) FROM violations 
                WHERE nguoi_lap_bien_ban = :officer_id AND trang_thai = 'Đã thanh toán'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':officer_id' => $officerId]);
        return $stmt->fetchColumn() ?? 0;
    }
}

// Router
if (isset($_GET['action'])) {
    $controller = new OfficerController();
    
    switch ($_GET['action']) {
        case 'dashboard':
            $controller->dashboard();
            break;
        case 'createManualViolation':
            $controller->createManualViolation();
            break;
        case 'processManualViolation':
            $controller->processManualViolation();
            break;
        case 'addViolation':
            $controller->addViolation();
            break;
        case 'exportPDF':
            $controller->exportPDF();
            break;
        case 'violationsList':
            $controller->violationsList();
            break;
        case 'viewViolation':
            $id = $_GET['id'] ?? 0;
            $controller->viewViolation($id);
            break;
        case 'updateStatus':
            $controller->updateViolationStatus();
            break;
        case 'searchViolation':
            $controller->searchViolation();
            break;
        case 'manualPayment':
            $controller->manualPayment();
            break;
        default:
            header('Location: /traffic/app/views/officers/login.php');
            break;
    }
}
?>