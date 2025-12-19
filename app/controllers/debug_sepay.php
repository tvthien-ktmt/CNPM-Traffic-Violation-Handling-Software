<?php
// debug_sepay.php - FIXED VERSION
// Đặt trong thư mục controllers

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>SePay Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-test { background: #28a745; }
        .btn-test:hover { background: #1e7e34; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .test-pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-fail { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>🔍 SePay Debug Tool - FIXED</h1>
";

// ========== KIỂM TRA HỆ THỐNG ==========
echo "<div class='section'>
    <h2>1. Kiểm tra Hệ thống</h2>";

// 1.1 Kiểm tra PHP
echo "<h3>PHP Environment</h3>";
echo "<p>PHP Version: <span class='" . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'success' : 'error') . "'>" . PHP_VERSION . "</span></p>";
echo "<p>Session: " . (session_status() === PHP_SESSION_ACTIVE ? '<span class="success">Active</span>' : '<span class="error">Not Active</span>') . "</p>";

// 1.2 Kiểm tra file tồn tại
echo "<h3>File Check</h3>";
$requiredFiles = [
    '../../config/database.php' => 'Database Config',
    '../models/Payment.php' => 'Payment Model',
    '../models/Violation.php' => 'Violation Model',
    'PaymentController.php' => 'Payment Controller'
];

foreach ($requiredFiles as $file => $name) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "<p>{$name}: " . ($exists ? '<span class="success">✓ Tồn tại</span>' : '<span class="error">✗ Không tồn tại</span>') . "</p>";
}

echo "</div>";

// ========== KIỂM TRA DATABASE ==========
echo "<div class='section'>
    <h2>2. Kiểm tra Database</h2>";

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<p>Database Connection: <span class='success'>✓ Connected</span></p>";
    
    // Kiểm tra bảng payments
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    $paymentsTable = $stmt->fetch();
    
    echo "<p>Payments Table: " . ($paymentsTable ? '<span class="success">✓ Tồn tại</span>' : '<span class="error">✗ Không tồn tại</span>') . "</p>";
    
    if ($paymentsTable) {
        // Kiểm tra cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE payments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Cấu trúc bảng Payments:</h4>";
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $importantColumns = ['so_tien_hien_thi', 'phep_chia', 'trang_thai', 'payment_group_id'];
        
        foreach ($columns as $col) {
            $class = in_array($col['Field'], $importantColumns) ? 'warning' : '';
            echo "<tr class='{$class}'>
                <td>{$col['Field']}</td>
                <td>{$col['Type']}</td>
                <td>{$col['Null']}</td>
                <td>{$col['Key']}</td>
                <td>{$col['Default']}</td>
            </tr>";
        }
        echo "</table>";
        
        // Lấy dữ liệu mẫu
        $stmt = $pdo->query("SELECT id, payment_group_id, so_tien, so_tien_hien_thi, phep_chia, trang_thai FROM payments ORDER BY created_at DESC LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($sampleData) {
            echo "<h4>Dữ liệu mẫu (5 bản ghi mới nhất):</h4>";
            echo "<table><tr><th>ID</th><th>Group ID</th><th>Số tiền gốc</th><th>Số tiền hiển thị</th><th>Phép chia</th><th>Trạng thái</th></tr>";
            foreach ($sampleData as $row) {
                $statusClass = ($row['trang_thai'] === 'Thành công') ? 'success' : (($row['trang_thai'] === 'Chờ thanh toán') ? 'warning' : '');
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['payment_group_id']}</td>
                    <td>" . number_format($row['so_tien']) . "</td>
                    <td>" . ($row['so_tien_hien_thi'] ? number_format($row['so_tien_hien_thi'], 2) : '0.00') . "</td>
                    <td>{$row['phep_chia']}</td>
                    <td class='{$statusClass}'>{$row['trang_thai']}</td>
                </tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Database Error: <span class='error'>" . $e->getMessage() . "</span></p>";
}

echo "</div>";

// ========== KIỂM TRA PAYMENT CONTROLLER ==========
echo "<div class='section'>
    <h2>3. Kiểm tra Payment Controller</h2>";

try {
    require_once __DIR__ . '/../models/Payment.php';
    require_once __DIR__ . '/../models/Violation.php';
    
    $paymentModel = new Payment();
    
    echo "<p>Payment Model: <span class='success'>✓ Loaded</span></p>";
    
    // Kiểm tra phương thức
    $methods = [
        'createPayment' => 'Tạo payment',
        'getPaymentGroupInfo' => 'Lấy group info',
        'updatePaymentByGroupId' => 'Update payment',
        'getPaymentByReference' => 'Tìm payment bằng reference'
    ];
    
    foreach ($methods as $method => $desc) {
        $exists = method_exists($paymentModel, $method);
        echo "<p>{$desc}: " . ($exists ? '<span class="success">✓ Tồn tại</span>' : '<span class="error">✗ Không tồn tại</span>') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Controller Error: <span class='error'>" . $e->getMessage() . "</span></p>";
}

echo "</div>";

// ========== KIỂM TRA API ENDPOINTS ==========
echo "<div class='section'>
    <h2>4. Kiểm tra API Endpoints</h2>
    
    <div class='test-result'>
        <h4>Test 1: Tạo thanh toán (Init)</h4>
        <a href='javascript:void(0)' onclick='testInit()' class='btn btn-test'>Test Init Payment</a>
        <div id='test-init-result'></div>
    </div>
    
    <div class='test-result'>
        <h4>Test 2: Kiểm tra thanh toán (Check)</h4>
        <p>Nhập Payment Group ID để test:</p>
        <input type='text' id='payment-group-id' placeholder='SEPAY_20251217060411_4848' style='padding: 5px; width: 300px; margin-bottom: 5px; display: block;'>
        <input type='text' id='payment-code' placeholder='VP_99C04350_1765947851_9748' style='padding: 5px; width: 300px; margin-bottom: 10px; display: block;'>
        <a href='javascript:void(0)' onclick='testCheck()' class='btn btn-test'>Test Check Payment</a>
        <div id='test-check-result'></div>
    </div>
    
    <div class='test-result'>
        <h4>Test 3: Kiểm tra Webhook</h4>
        <a href='javascript:void(0)' onclick='testWebhook()' class='btn btn-test'>Test Webhook Endpoint</a>
        <div id='test-webhook-result'></div>
    </div>
</div>";

// ========== KIỂM TRA SEPAY API ==========
echo "<div class='section'>
    <h2>5. Kiểm tra SePay API Configuration</h2>";

// Kiểm tra file config
$configFile = __DIR__ . '/../../config/sepay_config.php';
if (file_exists($configFile)) {
    $config = include $configFile;
    
    echo "<h4>SePay Config:</h4>";
    echo "<pre>" . htmlspecialchars(print_r($config, true)) . "</pre>";
    
    if (!empty($config['api_key'])) {
        echo "<p>API Key: <span class='success'>✓ Đã cấu hình</span> (" . substr($config['api_key'], 0, 10) . "...)</p>";
        
        // Test API connection
        echo "<div class='test-result'>
            <h4>Test kết nối SePay API</h4>
            <a href='javascript:void(0)' onclick='testSePayAPI()' class='btn btn-test'>Test SePay API</a>
            <div id='test-sepay-api-result'></div>
        </div>";
    } else {
        echo "<p>API Key: <span class='error'>✗ Chưa cấu hình</span></p>";
    }
} else {
    echo "<p>Config file: <span class='error'>✗ Không tồn tại</span></p>";
}

echo "</div>";

// ========== KIỂM TRA LỖI THƯỜNG GẶP ==========
echo "<div class='section'>
    <h2>6. Kiểm tra Lỗi Thường Gặp</h2>";

$commonIssues = [
    'QR hiển thị 0 đồng' => 'Kiểm tra so_tien_hien_thi trong database',
    'Polling không cập nhật' => 'Kiểm tra JavaScript console và API check',
    'Webhook không nhận' => 'Cấu hình webhook trong SePay dashboard',
    'Số tiền không khớp' => 'Kiểm tra phep_chia và so_sánh số tiền',
    'Database không cập nhật' => 'Kiểm tra quyền ghi vào database'
];

echo "<ul>";
foreach ($commonIssues as $issue => $solution) {
    echo "<li><strong>{$issue}:</strong> {$solution}</li>";
}
echo "</ul>";

// Kiểm tra lỗi cụ thể từ dữ liệu
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Kiểm tra payments có so_tien_hien_thi = 0
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments WHERE so_tien_hien_thi = 0 AND trang_thai = 'Chờ thanh toán'");
    $zeroAmount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($zeroAmount['count'] > 0) {
        echo "<p class='warning'>⚠ Có {$zeroAmount['count']} payments có so_tien_hien_thi = 0 (QR sẽ hiển thị 0 đồng)</p>";
    }
    
} catch (Exception $e) {
    // Ignore
}

echo "</div>";

// ========== HƯỚNG DẪN SỬA LỖI ==========
echo "<div class='section'>
    <h2>7. Hướng dẫn Debug</h2>
    
    <h3>Các bước kiểm tra:</h3>
    <ol>
        <li><strong>Kiểm tra logs</strong> - Xem error_log trong PHP và web server</li>
        <li><strong>Kiểm tra database</strong> - Xem trạng thái payments đã cập nhật chưa</li>
        <li><strong>Kiểm tra SePay Dashboard</strong> - Xem giao dịch đã thành công chưa</li>
        <li><strong>Kiểm tra Webhook</strong> - Xem SePay đã gửi webhook chưa</li>
        <li><strong>Kiểm tra Frontend</strong> - Xem console log và network requests</li>
    </ol>
    
    <h3>Manual Debug:</h3>
    <p>Test thủ công API endpoints:</p>
    
    <div style='background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0;'>
        <strong>Test Check Payment (copy và chạy trong terminal):</strong><br>
        <code style='display: block; margin: 5px 0; padding: 5px; background: white;'>
        curl \"http://localhost/traffic/app/controllers/PaymentController.php?action=check&payment_code=VP_99C04350_1765947851_9748&payment_id=SEPAY_20251217060411_4848\"
        </code>
    </div>
    
    <div style='background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0;'>
        <strong>Cập nhật trạng thái thủ công (chạy trong phpMyAdmin):</strong><br>
        <code style='display: block; margin: 5px 0; padding: 5px; background: white;'>
        UPDATE payments SET trang_thai = 'Thành công', thoi_gian_xac_nhan = NOW() WHERE payment_group_id = 'SEPAY_20251217060411_4848';
        </code>
    </div>
</div>";

// ========== JAVASCRIPT FUNCTIONS (ĐÃ SỬA) ==========
echo "
<script>
function testInit() {
    document.getElementById('test-init-result').innerHTML = '<p>Testing... ⏳</p>';
    
    fetch('PaymentController.php?action=init', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            violation_ids: [221, 153],
            license_plate: '99C04350',
            amount: 2000000
        })
    })
    .then(response => response.json())
    .then(data => {
        let html = '<div class=\"test-pass\"><strong>✓ Success!</strong><br>';
        html += 'Payment ID: ' + (data.payment_id || 'N/A') + '<br>';
        html += 'Display Amount: ' + (data.display_amount || 'N/A') + '<br>';
        html += 'Divide Rule: ' + (data.divide_rule || 'N/A') + '<br>';
        html += 'QR Code URL: ' + (data.qr_code_url ? '<a href=\"' + data.qr_code_url + '\" target=\"_blank\">View QR</a>' : 'N/A');
        html += '</div>';
        document.getElementById('test-init-result').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('test-init-result').innerHTML = 
            '<div class=\"test-fail\"><strong>✗ Error:</strong> ' + error.message + '</div>';
    });
}

function testCheck() {
    var groupId = document.getElementById('payment-group-id').value;
    var paymentCode = document.getElementById('payment-code').value;
    
    if (!groupId || !paymentCode) {
        alert('Vui lòng nhập Payment Group ID và Payment Code');
        return;
    }
    
    document.getElementById('test-check-result').innerHTML = '<p>Testing... ⏳</p>';
    
    var url = 'PaymentController.php?action=check&payment_code=' + encodeURIComponent(paymentCode) + '&payment_id=' + encodeURIComponent(groupId);
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        var html = '<div class=\"test-pass\"><strong>✓ API Response:</strong><br>';
        html += 'Success: ' + (data.success ? 'true' : 'false') + '<br>';
        html += 'Status: ' + (data.status || 'N/A') + '<br>';
        html += 'Message: ' + (data.message || 'N/A') + '<br>';
        
        if (data.details) {
            html += 'Details: ' + JSON.stringify(data.details, null, 2);
        }
        
        html += '</div>';
        document.getElementById('test-check-result').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('test-check-result').innerHTML = 
            '<div class=\"test-fail\"><strong>✗ Error:</strong> ' + error.message + '</div>';
    });
}

function testWebhook() {
    document.getElementById('test-webhook-result').innerHTML = '<p>Testing... ⏳</p>';
    
    fetch('PaymentController.php?action=webhook', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            event: 'transaction.completed',
            data: {
                reference_number: 'VP_TEST_' + Date.now(),
                amount: 2000,
                amount_in: 2000
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        var html = '<div class=\"test-pass\"><strong>✓ Webhook Response:</strong><br>';
        html += JSON.stringify(data, null, 2);
        html += '</div>';
        document.getElementById('test-webhook-result').innerHTML = html;
    })
    .catch(error => {
        document.getElementById('test-webhook-result').innerHTML = 
            '<div class=\"test-fail\"><strong>✗ Error:</strong> ' + error.message + '</div>';
    });
}

function testSePayAPI() {
    document.getElementById('test-sepay-api-result').innerHTML = '<p>Testing SePay API... ⏳</p>';
    
    // Gọi qua server để test API
    fetch('debug_sepay.php?test_sepay_api=1')
    .then(response => response.text())
    .then(data => {
        document.getElementById('test-sepay-api-result').innerHTML = 
            '<div class=\"test-pass\"><strong>✓ SePay API Test Result:</strong><br>' + data + '</div>';
    })
    .catch(error => {
        document.getElementById('test-sepay-api-result').innerHTML = 
            '<div class=\"test-fail\"><strong>✗ Error:</strong> ' + error.message + '</div>';
    });
}
</script>
";

// ========== PHP BACKEND TESTS ==========
if (isset($_GET['test_sepay_api'])) {
    try {
        require_once __DIR__ . '/../../config/database.php';
        
        // Kiểm tra API key từ config
        $configFile = __DIR__ . '/../../config/sepay_config.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            $apiKey = $config['api_key'] ?? '';
            
            if (!empty($apiKey)) {
                echo "<p>API Key found: " . substr($apiKey, 0, 10) . "...</p>";
                
                // Test API call
                $sepayApiUrl = "https://my.sepay.vn/userapi/transactions/list";
                $queryParams = http_build_query(['limit' => 1]);
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $sepayApiUrl . '?' . $queryParams,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiKey,
                        'Accept: application/json'
                    ],
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($curlError) {
                    echo "<p class='error'>✗ CURL Error: " . htmlspecialchars($curlError) . "</p>";
                } elseif ($httpCode === 200) {
                    echo "<p class='success'>✓ SePay API Connected (HTTP $httpCode)</p>";
                    $result = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        echo "<p>Response has transactions: " . (isset($result['transactions']) ? 'Yes (' . count($result['transactions']) . ')' : 'No') . "</p>";
                    } else {
                        echo "<p class='error'>✗ JSON Parse Error: " . json_last_error_msg() . "</p>";
                    }
                } else {
                    echo "<p class='error'>✗ SePay API Error (HTTP $httpCode)</p>";
                }
            } else {
                echo "<p class='error'>✗ No API Key found in config</p>";
            }
        }
        
        exit;
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
}

echo "</body></html>";