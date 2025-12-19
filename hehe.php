
<?php
session_start();

// Đường dẫn tuyệt đối
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/';

// Kiểm tra file video
echo "<h1>Kiểm tra file video</h1>";
$videos = [
    'nguyen-tat-thanh.webm' => $base_path . 'videos/nguyen-tat-thanh.webm',
    'dien-bien-phu.webm' => $base_path . 'videos/dien-bien-phu.webm'
];

foreach ($videos as $name => $path) {
    echo "<p><strong>$name:</strong> ";
    if (file_exists($path)) {
        $size = filesize($path);
        $size_mb = round($size / (1024 * 1024), 2);
        echo "✅ Tồn tại ($size_mb MB)";
    } else {
        echo "❌ Không tồn tại";
    }
    echo "</p>";
}

// Kiểm tra file ảnh
echo "<h1>Kiểm tra file ảnh</h1>";
$images = [
    'violation_1.jpg' => $base_path . 'images/violation_1.jpg',
    'violation_2.jpg' => $base_path . 'images/violation_2.jpg',
    'violation_3.jpg' => $base_path . 'images/violation_3.jpg'
];

foreach ($images as $name => $path) {
    echo "<p><strong>$name:</strong> ";
    if (file_exists($path)) {
        $size = filesize($path);
        $size_kb = round($size / 1024, 2);
        echo "✅ Tồn tại ($size_kb KB)";
    } else {
        echo "❌ Không tồn tại";
    }
    echo "</p>";
}

// Kiểm tra quyền
echo "<h1>Kiểm tra quyền truy cập</h1>";
echo "<p>Base path: " . $base_path . "</p>";
echo "<p>PHP user: " . get_current_user() . "</p>";

// Test đọc file
$test_file = $base_path . 'videos/nguyen-tat-thanh.webm';
if (file_exists($test_file)) {
    if (is_readable($test_file)) {
        echo "<p>✅ File có thể đọc được</p>";
    } else {
        echo "<p>❌ File không thể đọc được (permission issue)</p>";
    }
}
?>