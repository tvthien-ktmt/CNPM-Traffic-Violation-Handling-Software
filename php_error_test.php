
<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Error Test</h1>";

// Test 1: Kiểm tra session
echo "<h2>Test 1: Session</h2>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

// Test 2: Kiểm tra file includes
echo "<h2>Test 2: File Includes</h2>";
$files = [
    '/traffic/app/controllers/CameraController.php',
    '/traffic/app/views/camera/index.php',
    '/traffic/app/views/camera/view.php'
];

foreach ($files as $file) {
    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
    echo "File: $file<br>";
    echo "Exists: " . (file_exists($path) ? "✅ Yes" : "❌ No") . "<br>";
    echo "Readable: " . (is_readable($path) ? "✅ Yes" : "❌ No") . "<br><br>";
}

// Test 3: Kiểm tra PHP extensions
echo "<h2>Test 3: PHP Extensions</h2>";
$extensions = ['session', 'pdo', 'pdo_mysql', 'gd', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✅ Loaded" : "❌ Not loaded") . "<br>";
}

// Test 4: Test đường dẫn
echo "<h2>Test 4: Paths</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Test 5: Simple CameraController test
echo "<h2>Test 5: Simple CameraController Test</h2>";
echo '<a href="/traffic/test_camera_simple.php">Test Simple Camera Controller</a>';
?>
