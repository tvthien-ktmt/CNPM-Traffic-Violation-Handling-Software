<?php
// File: public/index.php
$request = $_SERVER['REQUEST_URI'];
echo "<!-- Debug: Request URI = $request -->";

// Remove project path if exists
$base_path = '/traffic';
$request = str_replace($base_path, '', $request);
$request = explode('?', $request)[0];

echo "<!-- Debug: Clean Request = $request -->";

// Kiểm tra file home có tồn tại không
$homeFile = '../app/views/home/index.php';
echo "<!-- Debug: Home file exists = " . (file_exists($homeFile) ? 'YES' : 'NO') . " -->";

// Routing
switch ($request) {
    case '/':
    case '/public/':
    case '':
        echo "<!-- Debug: Loading home page -->";
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
        echo "<h1>Đăng nhập cán bộ - Coming soon</h1>";
        break;
        
    default:
        http_response_code(404);
        echo '<h1>404 - Page not found</h1>';
        echo "<p>Request: $request</p>";
        break;
}
?>