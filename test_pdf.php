<?php
session_start();

// Định nghĩa đường dẫn font
define('FPDF_FONTPATH', $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/libs/fpdf/font/');

echo "<h2>1. Kiểm tra font path</h2>";
echo "FPDF_FONTPATH: " . FPDF_FONTPATH . "<br>";

if (is_dir(FPDF_FONTPATH)) {
    echo "✓ Thư mục font tồn tại<br>";
    $files = scandir(FPDF_FONTPATH);
    echo "Files: " . implode(", ", $files) . "<br>";
} else {
    echo "✗ Thư mục font không tồn tại!<br>";
    // Tạo thư mục
    mkdir(FPDF_FONTPATH, 0777, true);
    echo "Đã tạo thư mục font<br>";
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/libs/fpdf/fpdf.php';

echo "<h2>2. Test tạo PDF</h2>";

try {
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Test Helvetica (có sẵn trong core)
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Test với Helvetica', 0, 1, 'C');
    
    // Test Arial
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Test với Arial', 0, 1, 'L');
    
    // Test Times
    $pdf->SetFont('Times', 'I', 14);
    $pdf->Cell(0, 10, 'Test với Times', 0, 1, 'R');
    
    $pdf->Output('I', 'test.pdf');
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "<br>";
    
    // Debug thêm
    $pdf = new FPDF();
    $reflection = new ReflectionClass($pdf);
    
    // Lấy property fonts
    $fonts_prop = $reflection->getProperty('fonts');
    $fonts_prop->setAccessible(true);
    $fonts = $fonts_prop->getValue($pdf);
    
    echo "<h3>FPDF Fonts array:</h3>";
    echo "<pre>";
    print_r(array_keys($fonts));
    echo "</pre>";
    
    // Lấy property fontpath
    $fontpath_prop = $reflection->getProperty('fontpath');
    $fontpath_prop->setAccessible(true);
    $fontpath = $fontpath_prop->getValue($pdf);
    
    echo "Fontpath: " . $fontpath . "<br>";
}
?>