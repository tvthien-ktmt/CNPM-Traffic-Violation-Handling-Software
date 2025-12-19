<?php
/**
 * SEPAY CONFIGURATION
 * Cấu hình API Key và thông tin ngân hàng
 */

return [
    // API Key từ my.sepay.vn
    'api_key' => 'ZGF5STL6VFGVIHS34SMLU8X1K0EBWXMB2R5ZRCWNCPFORBYKY7V7FXLVWAPPOMUD',
    
    // Thông tin ngân hàng nhận tiền
    'bank_account' => '96247LVTH1809',
    'bank_code' => 'BIDV',
    'bank_name' => 'BIDV',
    'account_name' => 'LUU VAN THANH HUY',
    
    // Webhook Secret (Tự tạo chuỗi ngẫu nhiên để bảo mật)
    'webhook_secret' => 'YOUR_RANDOM_SECRET_STRING_HERE',
    
    // QR Code Template
    'qr_template' => 'compact'
];
?>