
<?php
echo "<h1>Kiểm tra đường dẫn</h1>";

// Kiểm tra các đường dẫn
$paths = [
    'Document Root' => $_SERVER['DOCUMENT_ROOT'],
    'Request URI' => $_SERVER['REQUEST_URI'] ?? '',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? '',
    'PHP Self' => $_SERVER['PHP_SELF'] ?? ''
];

echo "<pre>";
foreach ($paths as $name => $path) {
    echo "$name: $path\n";
}
echo "</pre>";

// Kiểm tra các file
echo "<h2>Kiểm tra file tồn tại:</h2>";

$test_files = [
    'Video 1' => 'D:/xampp/htdocs/traffic/public/assets/videos/nguyen-tat-thanh.webm',
    'Video 2' => 'D:/xampp/htdocs/traffic/public/assets/videos/dien-bien-phu.webm',
    'Image 1' => 'D:/xampp/htdocs/traffic/public/assets/images/violation_1.jpg',
    'Image 2' => 'D:/xampp/htdocs/traffic/public/assets/images/violation_2.jpg',
    'Image 3' => 'D:/xampp/htdocs/traffic/public/assets/images/violation_3.jpg'
];

foreach ($test_files as $name => $path) {
    echo "<p><strong>$name:</strong> ";
    if (file_exists($path)) {
        $size = filesize($path);
        $size_mb = round($size / (1024 * 1024), 2);
        echo "✅ Tồn tại ($size_mb MB) - ";
        echo "<a href='/traffic/public/assets/" . basename(dirname($path)) . "/" . basename($path) . "' target='_blank'>Test Link</a>";
    } else {
        echo "❌ Không tồn tại";
    }
    echo "</p>";
}

// Test URL trực tiếp
echo "<h2>Test URL trực tiếp:</h2>";
echo '<ul>';
echo '<li><a href="/traffic/public/assets/videos/nguyen-tat-thanh.webm" target="_blank">/traffic/public/assets/videos/nguyen-tat-thanh.webm</a></li>';
echo '<li><a href="/traffic/public/assets/videos/dien-bien-phu.webm" target="_blank">/traffic/public/assets/videos/dien-bien-phu.webm</a></li>';
echo '<li><a href="/traffic/public/assets/images/violation_1.jpg" target="_blank">/traffic/public/assets/images/violation_1.jpg</a></li>';
echo '<li><a href="/traffic/public/assets/images/violation_2.jpg" target="_blank">/traffic/public/assets/images/violation_2.jpg</a></li>';
echo '<li><a href="/traffic/public/assets/images/violation_3.jpg" target="_blank">/traffic/public/assets/images/violation_3.jpg</a></li>';
echo '</ul>';

// Kiểm tra .htaccess hoặc cấu hình server
echo "<h2>Kiểm tra cấu hình:</h2>";
echo "<p>Kiểm tra file .htaccess trong thư mục public:</p>";
$htaccess_path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/.htaccess';
if (file_exists($htaccess_path)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccess_path)) . "</pre>";
} else {
    echo "<p>Không tìm thấy .htaccess</p>";
}
?>
