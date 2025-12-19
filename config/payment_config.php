<?php
return [
    'vnpay' => [
        'vnp_TmnCode' => 'DEMOVNPAY', // Mã test
        'vnp_HashSecret' => 'PAYMENTSECRET123', // Secret test
        'vnp_Url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
        'vnp_ReturnUrl' => 'http://localhost/traffic/app/controllers/PaymentController.php?action=callback'
    ]
];
?>