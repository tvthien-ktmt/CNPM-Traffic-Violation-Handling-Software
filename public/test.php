
<?php
// Test file để kiểm tra đường dẫn
echo "<h1>Test File Access</h1>";

// Test video files
$video_files = [
    '/traffic/public/assets/videos/nguyen-tat-thanh.webm',
    '/traffic/public/assets/videos/dien-bien-phu.webm'
];

echo "<h2>Test Video Links:</h2>";
foreach ($video_files as $video) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $video;
    echo "<p>";
    echo "<strong>$video</strong>: ";
    if (file_exists($full_path)) {
        echo "✅ File exists | ";
        echo "<a href='$video' target='_blank'>Open</a> | ";
        echo "<a href='$video' download>Download</a>";
    } else {
        echo "❌ File not found";
        echo "<br>Full path: " . htmlspecialchars($full_path);
    }
    echo "</p>";
}

// Test image files
$image_files = [
    '/traffic/public/assets/images/violation_1.jpg',
    '/traffic/public/assets/images/violation_2.jpg',
    '/traffic/public/assets/images/violation_3.jpg'
];

echo "<h2>Test Image Links:</h2>";
echo "<div style='display: flex; gap: 10px;'>";
foreach ($image_files as $image) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . $image;
    echo "<div style='text-align: center;'>";
    echo "<p><strong>" . basename($image) . "</strong><br>";
    if (file_exists($full_path)) {
        echo "✅<br>";
        echo "<img src='$image' style='width: 200px; height: 150px; object-fit: cover;'><br>";
        echo "<a href='$image' target='_blank'>View</a>";
    } else {
        echo "❌ Not found";
    }
    echo "</p></div>";
}
echo "</div>";

// Test server variables
echo "<h2>Server Information:</h2>";
echo "<pre>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "</pre>";
?>
