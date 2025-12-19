<?php
ob_start();

$tcpdf_path = 'D:/xampp/htdocs/traffic/app/libs/tcpdf/tcpdf.php';

if (file_exists($tcpdf_path)) {
    require_once($tcpdf_path);
    
    try {
        // Dữ liệu test
        $test_data = [
            'report_number' => 'BB202512161234',
            'officer_name' => 'Nguyễn Văn A',
            'officer_position' => 'Trung sĩ',
            'officer_unit' => 'Đội CSGT Quận Ba Đình',
            'violator_name' => 'Trần Văn B',
            'violator_id' => '0123456789',
            'violator_phone' => '0987654321',
            'violator_address' => '123 Đường Láng Hạ, Ba Đình, Hà Nội',
            'violator_birthday' => '1990-01-01',
            'violator_nationality' => 'Việt Nam',
            'license_plate' => '29A1-12345',
            'vehicle_type' => 'Xe máy',
            'vehicle_color' => 'Đen',
            'vehicle_brand' => 'Honda',
            'violation_date' => date('Y-m-d'),
            'violation_time' => date('H:i'),
            'violation_location' => 'Đường Láng Hạ, Ba Đình, Hà Nội',
            'violation_type' => ['Vượt đèn đỏ', 'Không đội mũ bảo hiểm'],
            'violation_content' => 'Xe máy biển số 29A1-12345 vượt đèn đỏ tại ngã tư Láng Hạ - Giảng Võ',
            'fine_amount' => 1300000,
            'notes' => '',
            'legal_basis' => 'Nghị định 100/2019/NĐ-CP',
            'report_date' => date('d/m/Y'),
            'report_time' => date('H:i')
        ];
        
        // Tạo PDF test
        class TestPDF extends TCPDF {
            private $data;
            
            function __construct($data) {
                parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
                $this->data = $data;
                
                $this->SetCreator('Hệ thống CSGT');
                $this->SetAuthor('Cán bộ CSGT');
                $this->SetTitle('Test PDF');
                
                $this->SetMargins(15, 15, 15);
                $this->SetAutoPageBreak(true, 15);
                
                $this->AddPage();
                $this->createContent();
            }
            
            function createContent() {
                $this->SetFont('dejavuserif', 'B', 16);
                $this->Cell(0, 10, 'TEST PDF THÀNH CÔNG', 0, 1, 'C');
                $this->Ln(10);
                
                $this->SetFont('dejavuserif', '', 12);
                $this->Cell(0, 10, 'Số biên bản: ' . $this->data['report_number'], 0, 1);
                $this->Cell(0, 10, 'Tên người vi phạm: ' . $this->data['violator_name'], 0, 1);
                $this->Cell(0, 10, 'Biển số: ' . $this->data['license_plate'], 0, 1);
                $this->Cell(0, 10, 'Tiền phạt: ' . number_format($this->data['fine_amount'], 0, ',', '.') . ' VNĐ', 0, 1);
                $this->Ln(10);
                
                $this->SetFont('dejavuserif', '', 12);
                $this->MultiCell(0, 10, 'Tiếng Việt có dấu: Đây là test PDF với TCPDF và font DejaVu Serif. Mọi thứ đều hiển thị tốt.');
            }
        }
        
        $pdf = new TestPDF($test_data);
        
        // Xuất PDF theo nhiều cách để test
        $filename = 'test_output.pdf';
        
        // Cách 1: Inline (hiển thị trong trình duyệt)
        $pdf->Output($filename, 'I');
        
        // // Cách 2: Download
        // $pdf->Output($filename, 'D');
        
        // // Cách 3: Lưu file
        // $pdf->Output('D:/xampp/htdocs/traffic/temp/test.pdf', 'F');
        // echo "Đã lưu file thành công!";
        
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "Lỗi: " . $e->getMessage();
        echo "<br>Trace: " . $e->getTraceAsString();
    }
    
} else {
    ob_end_clean();
    echo "Không tìm thấy TCPDF!";
}
?>