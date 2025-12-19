<?php
/**
 * Export Receipt PDF
 * File: app/views/violations/export_receipt.php
 * 
 * Xuất biên lai thanh toán dưới dạng PDF
 * Sử dụng TCPDF hoặc DomPDF (cần cài đặt)
 */

require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../models/Violation.php';

$transactionCode = $_GET['code'] ?? '';

if (empty($transactionCode)) {
    die('Mã giao dịch không hợp lệ');
}

// Lấy thông tin thanh toán
$paymentModel = new Payment();
$violationModel = new Violation();

$payment = $paymentModel->getPaymentByCode($transactionCode);

if (!$payment || $payment['trang_thai'] !== 'Thành công') {
    die('Không tìm thấy thông tin thanh toán');
}

$violation = $violationModel->getViolationById($payment['violation_id']);

// Tạo HTML cho biên lai
ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Biên Lai Thanh Toán</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #004aad;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #004aad;
            margin: 10px 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #dc3545;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            font-weight: bold;
            padding: 8px 0;
        }
        .info-value {
            display: table-cell;
            width: 60%;
            padding: 8px 0;
        }
        .amount-box {
            background: #f8f9fa;
            border: 2px solid #004aad;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        .amount-label {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount-value {
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
        }
        .amount-text {
            font-style: italic;
            margin-top: 10px;
            color: #666;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        .signature-col {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
        }
        .signature-name {
            font-style: italic;
        }
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
        }
        .stamp {
            position: absolute;
            right: 50px;
            bottom: 100px;
            opacity: 0.3;
            transform: rotate(-15deg);
            font-size: 48px;
            color: #dc3545;
            border: 5px solid #dc3545;
            padding: 10px 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>CỤC CẢNH SÁT GIAO THÔNG</h1>
        <p>Bộ Công An Việt Nam</p>
        <p>Địa chỉ: 62 Nguyễn Du, Hà Nội | Hotline: 1900-xxxx</p>
    </div>

    <!-- Tiêu đề -->
    <div class="title">Biên Lai Thanh Toán Phạt Vi Phạm Giao Thông</div>

    <!-- Thông tin giao dịch -->
    <div class="info-section">
        <h3 style="color: #004aad; border-bottom: 2px solid #004aad; padding-bottom: 10px;">
            THÔNG TIN GIAO DỊCH
        </h3>
        
        <div class="info-row">
            <span class="info-label">Mã giao dịch:</span>
            <span class="info-value"><?= htmlspecialchars($payment['ma_thanh_toan']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Mã vi phạm:</span>
            <span class="info-value"><?= htmlspecialchars($violation['ma_vi_pham'] ?? 'N/A') ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Thời gian thanh toán:</span>
            <span class="info-value">
                <?= date('d/m/Y H:i:s', strtotime($payment['thoi_gian_thanh_toan'])) ?>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Phương thức:</span>
            <span class="info-value">Chuyển khoản ngân hàng (SePay)</span>
        </div>
    </div>

    <!-- Thông tin người thanh toán -->
    <div class="info-section">
        <h3 style="color: #004aad; border-bottom: 2px solid #004aad; padding-bottom: 10px;">
            THÔNG TIN NGƯỜI THANH TOÁN
        </h3>
        
        <div class="info-row">
            <span class="info-label">Họ và tên:</span>
            <span class="info-value"><?= htmlspecialchars($payment['ho_ten']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">CCCD/CMND:</span>
            <span class="info-value"><?= htmlspecialchars($violation['cccd'] ?? 'N/A') ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Số điện thoại:</span>
            <span class="info-value"><?= htmlspecialchars($payment['so_dien_thoai']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Địa chỉ:</span>
            <span class="info-value"><?= htmlspecialchars($violation['dia_chi'] ?? 'N/A') ?></span>
        </div>
    </div>

    <!-- Thông tin vi phạm -->
    <div class="info-section">
        <h3 style="color: #004aad; border-bottom: 2px solid #004aad; padding-bottom: 10px;">
            THÔNG TIN VI PHẠM
        </h3>
        
        <div class="info-row">
            <span class="info-label">Biển số xe:</span>
            <span class="info-value" style="font-weight: bold; color: #dc3545;">
                <?= htmlspecialchars($payment['bien_so']) ?>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Loại xe:</span>
            <span class="info-value"><?= htmlspecialchars($violation['loai_xe'] ?? 'N/A') ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Loại vi phạm:</span>
            <span class="info-value"><?= htmlspecialchars($violation['ten_loi'] ?? 'N/A') ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Thời gian vi phạm:</span>
            <span class="info-value">
                <?= date('d/m/Y H:i', strtotime($violation['thoi_gian_vi_pham'] ?? '')) ?>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Địa điểm vi phạm:</span>
            <span class="info-value"><?= htmlspecialchars($violation['dia_diem'] ?? 'N/A') ?></span>
        </div>
    </div>

    <!-- Số tiền -->
    <div class="amount-box">
        <div class="amount-label">TỔNG SỐ TIỀN ĐÃ THANH TOÁN</div>
        <div class="amount-value"><?= number_format($payment['so_tien'], 0, ',', '.') ?> VNĐ</div>
        <div class="amount-text">
            (<?= convertNumberToWords($payment['so_tien']) ?> đồng)
        </div>
    </div>

    <!-- Ghi chú -->
    <div class="note">
        <strong>Lưu ý:</strong><br>
        - Biên lai này là bằng chứng xác nhận đã thanh toán phạt vi phạm giao thông.<br>
        - Vi phạm đã được gỡ khỏi hệ thống tra cứu.<br>
        - Vui lòng giữ biên lai để xuất trình khi cần thiết.<br>
        - Mọi thắc mắc xin liên hệ: hotline 1900-xxxx hoặc email: csgt@conganthanhpho.vn
    </div>

    <!-- Chữ ký -->
    <div class="signature">
        <div class="signature-col">
            <div class="signature-title">Người thanh toán</div>
            <div class="signature-name">(Ký và ghi rõ họ tên)</div>
        </div>
        <div class="signature-col">
            <div class="signature-title">Người xác nhận</div>
            <div class="signature-name">(Ký, đóng dấu)</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p style="text-align: center; color: #666; font-size: 12px;">
            Biên lai được in từ hệ thống tự động vào lúc <?= date('d/m/Y H:i:s') ?><br>
            Website: https://csgt.gov.vn | Email: info@csgt.gov.vn
        </p>
    </div>

    <!-- Dấu đã thanh toán -->
    <div class="stamp">ĐÃ THANH TOÁN</div>
</body>
</html>
<?php
$html = ob_get_clean();

/**
 * Chuyển số thành chữ (tiếng Việt)
 */
function convertNumberToWords($number) {
    $number = intval($number);
    
    $ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
    $hundreds = ['', 'một trăm', 'hai trăm', 'ba trăm', 'bốn trăm', 'năm trăm', 'sáu trăm', 'bảy trăm', 'tám trăm', 'chín trăm'];
    
    if ($number == 0) return 'không';
    
    $billion = floor($number / 1000000000);
    $number = $number % 1000000000;
    
    $million = floor($number / 1000000);
    $number = $number % 1000000;
    
    $thousand = floor($number / 1000);
    $number = $number % 1000;
    
    $result = '';
    
    if ($billion) {
        $result .= convertThreeDigits($billion, $ones, $tens, $hundreds) . ' tỷ ';
    }
    
    if ($million) {
        $result .= convertThreeDigits($million, $ones, $tens, $hundreds) . ' triệu ';
    }
    
    if ($thousand) {
        $result .= convertThreeDigits($thousand, $ones, $tens, $hundreds) . ' nghìn ';
    }
    
    if ($number) {
        $result .= convertThreeDigits($number, $ones, $tens, $hundreds);
    }
    
    return ucfirst(trim($result));
}

function convertThreeDigits($number, $ones, $tens, $hundreds) {
    $result = '';
    
    $h = floor($number / 100);
    $t = floor(($number % 100) / 10);
    $o = $number % 10;
    
    if ($h) {
        $result .= $hundreds[$h] . ' ';
    }
    
    if ($t >= 2) {
        $result .= $tens[$t] . ' ';
        if ($o) {
            $result .= $ones[$o];
        }
    } elseif ($t == 1) {
        $result .= 'mười ';
        if ($o) {
            $result .= $ones[$o];
        }
    } else {
        if ($o && $h) {
            $result .= 'lẻ ';
        }
        if ($o) {
            $result .= $ones[$o];
        }
    }
    
    return trim($result);
}

// ============================================
// XUẤT PDF
// ============================================

// Phương án 1: Sử dụng DomPDF (cần cài: composer require dompdf/dompdf)
if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
    
    use Dompdf\Dompdf;
    use Dompdf\Options;
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $fileName = 'bien_lai_' . $transactionCode . '.pdf';
    $dompdf->stream($fileName, ['Attachment' => 1]);
    
} else {
    // Phương án 2: Output HTML trực tiếp (fallback)
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    echo '<script>window.print();</script>';
}
?>