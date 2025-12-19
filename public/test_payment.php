<?php
// Test PaymentController
require_once __DIR__ . '/../config/payment_config.php';

echo "<h2>Test Payment Config</h2>";
echo "VNPAY_TMN_CODE: " . VNPAY_TMN_CODE . "<br>";
echo "VNPAY_URL: " . VNPAY_URL . "<br>";
echo "VNPAY_RETURN_URL: " . VNPAY_RETURN_URL . "<br>";

// Test tạo mã giao dịch
$code = generate_transaction_code('TEST');
echo "<br>Transaction Code: " . $code . "<br>";

// Test format amount
$amount = format_vnpay_amount(1000000);
echo "Formatted Amount: " . $amount . "<br>";
?>