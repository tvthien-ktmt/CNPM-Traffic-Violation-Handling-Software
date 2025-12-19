<?php
ob_start();

$tcpdf_path = 'D:/xampp/htdocs/traffic/app/libs/tcpdf/tcpdf.php';

if (file_exists($tcpdf_path)) {
    require_once($tcpdf_path);
    
    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        ob_end_clean();
        
        $pdf->AddPage();
        
        // THỬ VỚI DEJAVUSERIF (giống Times New Roman)
        $pdf->SetFont('dejavuserif', 'B', 16);
        $pdf->Cell(0, 10, 'TEST TCPDF THÀNH CÔNG', 0, 1, 'C');
        
        $pdf->SetFont('dejavuserif', '', 12);
        $pdf->Cell(0, 10, 'Font DejaVu Serif hoạt động', 0, 1);
        
        // Tiếng Việt với DejaVu Serif
        $pdf->SetFont('dejavuserif', '', 12);
        $pdf->Cell(0, 10, 'Tiếng Việt có dấu: Đây là test DejaVu Serif', 0, 1);
        
        // Test thêm
        $pdf->SetFont('dejavuserif', 'I', 12);
        $pdf->Cell(0, 10, 'Chữ in nghiêng: Tiếng Việt', 0, 1);
        
        $pdf->SetFont('dejavuserif', 'B', 12);
        $pdf->Cell(0, 10, 'Chữ in đậm: TIẾNG VIỆT', 0, 1);
        
        $pdf->Output('test_tcpdf_dejavuserif.pdf', 'I');
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        die("Lỗi: " . $e->getMessage());
    }
    
} else {
    ob_end_clean();
    die("File tcpdf.php không tồn tại!");
}
?>