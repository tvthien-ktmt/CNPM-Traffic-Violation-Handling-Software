<?php

// =========================
// 1. Auto-run FastAPI
// =========================
function isPortInUse($port) {
    $connection = @fsockopen('127.0.0.1', $port);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

// Chỉ chạy FastAPI khi port 8000 chưa được sử dụng
if (!isPortInUse(8000)) {
    $batFile = "D:\\xampp\\htdocs\\traffic\\start_fastapi.bat";
    if (file_exists($batFile)) {
        pclose(popen("cmd.exe /c \"$batFile\"", "r"));
    } else {
        error_log("FastAPI batch file not found: $batFile");
    }
}

// =========================
// 2. ROUTING GỐC
// =========================

$request = $_SERVER['REQUEST_URI'];
echo "<!-- Debug: Request URI = $request -->";

$base_path = '/traffic';
$request = str_replace($base_path, '', $request);
$request = explode('?', $request)[0];

echo "<!-- Debug: Clean Request = $request -->";

// Kiểm tra nếu đang ở trang login
if ($request === '/officers/login' || $request === '/app/views/officers/login.php') {
    // Chuyển hướng đến trang login thực sự
    header('Location: /traffic/app/views/officers/login.php');
    exit();
}

$homeFile = '../app/views/home/index.php';
echo "<!-- Debug: Home file exists = " . (file_exists($homeFile) ? 'YES' : 'NO') . " -->";

switch ($request) {
    case '/':
    case '/public/':
    case '':
        if (file_exists($homeFile)) {
            include $homeFile;
        } else {
            echo "<h1>Trang chủ Traffic System</h1>";
            echo "<p>File app/views/home/index.php không tồn tại</p>";
        }
        break;

    case '/violations/search':
        echo "<h1>Tra cứu vi phạm - Coming soon</h1>";
        break;

    case '/officers/login':
        // Đã xử lý redirect ở trên
        break;

    default:
        // Kiểm tra xem có phải là file PHP không
        if (preg_match('/\.php$/', $request)) {
            $filePath = '../app' . $request;
            if (file_exists($filePath)) {
                include $filePath;
            } else {
                http_response_code(404);
                echo '<h1>404 - Page not found</h1>';
                echo "<p>File not found: $filePath</p>";
            }
        } else {
            http_response_code(404);
            echo '<h1>404 - Page not found</h1>';
            echo "<p>Request: $request</p>";
        }
        break;
}
?>