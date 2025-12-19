
<?php
// Test file video tồn tại hay không
echo "<h1>KIỂM TRA FILE VIDEO VÀ ẢNH</h1>";
echo "<p>Thư mục gốc: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<hr>";

// Kiểm tra video
$videos = [
    'nguyen-tat-thanh.mp4.mp4',
    'dien-bien-phu.mp4.mp4'
];

echo "<h2>1. KIỂM TRA VIDEO</h2>";

foreach ($videos as $video) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/' . $video;
    $url = '/traffic/public/assets/videos/' . $video;
    
    echo "<div style='border:1px solid #ccc; padding:15px; margin:10px; background:#f9f9f9;'>";
    echo "<h3>File: $video</h3>";
    
    if (file_exists($path)) {
        $size = filesize($path);
        $size_mb = round($size/1024/1024, 2);
        
        echo "<p style='color:green; font-weight:bold;'>✓ TỒN TẠI</p>";
        echo "<p><strong>Đường dẫn tuyệt đối:</strong><br>$path</p>";
        echo "<p><strong>URL truy cập:</strong><br><a href='$url' target='_blank'>$url</a></p>";
        echo "<p><strong>Kích thước:</strong> " . number_format($size) . " bytes ($size_mb MB)</p>";
        
        // Kiểm tra MIME type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            echo "<p><strong>MIME Type:</strong> $mime</p>";
        }
        
        // Kiểm tra quyền đọc
        echo "<p><strong>Có thể đọc:</strong> " . (is_readable($path) ? "✓ CÓ" : "✗ KHÔNG") . "</p>";
        
        // Hiển thị video test
        echo "<div style='margin-top:10px;'>";
        echo "<p><strong>Test phát video:</strong></p>";
        echo "<video controls width='500' preload='metadata' style='max-width:100%;'>
                <source src='$url' type='video/mp4'>
                Trình duyệt không hỗ trợ video HTML5
              </video>";
        echo "</div>";
        
    } else {
        echo "<p style='color:red; font-weight:bold;'>✗ KHÔNG TỒN TẠI</p>";
        echo "<p><strong>Đường dẫn kiểm tra:</strong><br>$path</p>";
        echo "<p><strong>Thư mục videos có tồn tại?</strong> " . (is_dir($_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/') ? "CÓ" : "KHÔNG") . "</p>";
    }
    
    echo "</div>";
}

// Kiểm tra ảnh
echo "<h2>2. KIỂM TRA ẢNH VI PHẠM</h2>";

$images = ['violation_1.jpg', 'violation_2.jpg', 'violation_3.jpg'];

foreach ($images as $image) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/images/' . $image;
    $url = '/traffic/public/assets/images/' . $image;
    
    echo "<div style='display:inline-block; margin:10px; padding:10px; border:1px solid #ddd; vertical-align:top;'>";
    
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<p style='color:green;'><strong>✓ $image</strong></p>";
        echo "<p>Kích thước: " . number_format($size) . " bytes</p>";
        echo "<img src='$url' width='150' style='border:1px solid #ccc; max-height:100px; object-fit:cover;'>";
        echo "<p><a href='$url' target='_blank'>Mở ảnh</a></p>";
    } else {
        echo "<p style='color:red;'><strong>✗ $image</strong></p>";
        echo "<p>Không tìm thấy</p>";
    }
    
    echo "</div>";
}

// Kiểm tra cấu trúc thư mục
echo "<h2>3. KIỂM TRA CẤU TRÚC THƯ MỤC</h2>";

function listDirectory($dir, $prefix = '') {
    $result = '';
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $fullpath = $dir . '/' . $item;
                if (is_dir($fullpath)) {
                    $result .= $prefix . "📁 $item/<br>";
                    $result .= listDirectory($fullpath, $prefix . "&nbsp;&nbsp;&nbsp;&nbsp;");
                } else {
                    $size = filesize($fullpath);
                    $result .= $prefix . "📄 $item (" . number_format($size) . " bytes)<br>";
                }
            }
        }
    } else {
        $result = $prefix . "❌ Thư mục không tồn tại<br>";
    }
    return $result;
}

echo "<div style='background:#f0f0f0; padding:10px; font-family:monospace;'>";
echo "📁 traffic/<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;📁 public/<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;📁 assets/<br>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;📁 videos/<br>";
echo listDirectory($_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/', "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;📁 images/<br>";
echo listDirectory($_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/images/', "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
echo "</div>";

// Kiểm tra PHP configuration
echo "<h2>4. THÔNG TIN CẤU HÌNH PHP</h2>";
echo "<pre style='background:#f0f0f0; padding:10px;'>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";

echo "<h2>5. KIỂM TRA TRỰC TIẾP TRONG TRÌNH DUYỆT</h2>";
echo "<p>Mở các link sau trong tab mới để test:</p>";
echo "<ul>";
echo "<li><a href='/traffic/public/assets/videos/nguyen-tat-thanh.mp4.mp4' target='_blank'>Video Nguyễn Tất Thành</a></li>";
echo "<li><a href='/traffic/public/assets/videos/dien-bien-phu.mp4.mp4' target='_blank'>Video Điện Biên Phủ</a></li>";
echo "<li><a href='/traffic/public/assets/images/violation_1.jpg' target='_blank'>Ảnh violation_1.jpg</a></li>";
echo "<li><a href='/traffic/public/assets/images/violation_2.jpg' target='_blank'>Ảnh violation_2.jpg</a></li>";
echo "<li><a href='/traffic/public/assets/images/violation_3.jpg' target='_blank'>Ảnh violation_3.jpg</a></li>";
echo "</ul>";

echo "<hr><p style='color:blue;'><strong>Hướng dẫn:</strong> Chạy file này bằng cách truy cập http://localhost/traffic/test_video.php</p>";
?>
