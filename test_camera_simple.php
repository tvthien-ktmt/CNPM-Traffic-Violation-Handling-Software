
<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Mock session cho test
$_SESSION['officer_id'] = 1;
$_SESSION['officer_name'] = 'Cán bộ Test';

$cameras = [
    [
        'id' => 1,
        'name' => 'Camera Nguyễn Tất Thành',
        'location' => 'Ngã tư Nguyễn Tất Thành - Lê Duẩn, Quận 1',
        'video_file' => 'nguyen-tat-thanh.mp4.mp4',
        'status' => 'online'
    ],
    [
        'id' => 2,
        'name' => 'Camera Điện Biên Phủ',
        'location' => 'Đoạn Điện Biên Phủ - Pasteur, Quận 1',
        'video_file' => 'dien-bien-phu.mp4.mp4',
        'status' => 'online'
    ]
];

// Lấy camera ID từ URL
$camera_id = $_GET['id'] ?? 1;
$camera = $cameras[$camera_id - 1] ?? $cameras[0];

// Kiểm tra video
$video_path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/' . $camera['video_file'];
$video_url = 'http://' . $_SERVER['HTTP_HOST'] . '/traffic/public/assets/videos/' . $camera['video_file'];
$video_exists = file_exists($video_path);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Camera Video</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .header { background: #2563eb; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .video-container { background: #000; padding: 10px; border-radius: 10px; margin: 20px 0; }
        video { width: 100%; max-width: 800px; display: block; margin: 0 auto; }
        .info { background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Test Camera Video System</h1>
        <p>Kiểm tra hệ thống camera và video streaming</p>
    </div>
    
    <div class="info">
        <h2>Camera: <?php echo htmlspecialchars($camera['name']); ?></h2>
        <p><strong>Vị trí:</strong> <?php echo htmlspecialchars($camera['location']); ?></p>
        <p><strong>File video:</strong> <?php echo htmlspecialchars($camera['video_file']); ?></p>
        <p><strong>Tồn tại:</strong> 
            <?php if ($video_exists): ?>
                <span class="success">✅ CÓ (<?php echo number_format(filesize($video_path)); ?> bytes)</span>
            <?php else: ?>
                <span class="error">❌ KHÔNG</span>
            <?php endif; ?>
        </p>
        <p><strong>Đường dẫn:</strong> <?php echo htmlspecialchars($video_path); ?></p>
        <p><strong>URL:</strong> <a href="<?php echo htmlspecialchars($video_url); ?>" target="_blank"><?php echo htmlspecialchars($video_url); ?></a></p>
    </div>
    
    <div style="margin: 20px 0;">
        <a href="?id=1" class="btn">Camera 1 (Nguyễn Tất Thành)</a>
        <a href="?id=2" class="btn">Camera 2 (Điện Biên Phủ)</a>
        <a href="/traffic/php_error_test.php" class="btn" style="background: #dc2626;">Test Lỗi PHP</a>
        <a href="/traffic/app/controllers/OfficerController.php?action=dashboard" class="btn" style="background: #059669;">Quay lại Dashboard</a>
    </div>
    
    <?php if ($video_exists): ?>
    <div class="video-container">
        <h2 style="color: white; text-align: center;">Video Player Test</h2>
        <video id="testVideo" controls preload="auto" playsinline>
            <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
            <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4; codecs=avc1.42E01E,mp4a.40.2">
            Trình duyệt của bạn không hỗ trợ video HTML5.
        </video>
        <div style="text-align: center; margin-top: 10px;">
            <button onclick="playVideo()" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">▶ Phát Video</button>
            <button onclick="pauseVideo()" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">⏸ Dừng Video</button>
            <button onclick="reloadVideo()" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">↻ Tải lại Video</button>
        </div>
    </div>
    
    <div class="info">
        <h3>Thông tin Video</h3>
        <div id="videoInfo">Đang kiểm tra...</div>
    </div>
    <?php else: ?>
    <div style="background: #fef2f2; border: 2px solid #f87171; padding: 20px; border-radius: 8px;">
        <h2 style="color: #dc2626;">⚠️ LỖI: Video không tồn tại</h2>
        <p>File video: <strong><?php echo htmlspecialchars($camera['video_file']); ?></strong></p>
        <p>Đường dẫn kiểm tra: <code><?php echo htmlspecialchars($video_path); ?></code></p>
        <p>Kiểm tra:</p>
        <ol>
            <li>File có tồn tại trong thư mục videos không?</li>
            <li>Tên file có đúng không? (chú ý .mp4.mp4)</li>
            <li>Quyền đọc file?</li>
        </ol>
        <p><strong>Giải pháp tạm thời:</strong> Đổi tên file video thành .mp4 đơn (bỏ .mp4 thừa)</p>
    </div>
    <?php endif; ?>
    
    <script>
        const video = document.getElementById('testVideo');
        
        function playVideo() {
            if (video) {
                video.play().catch(error => {
                    alert('Lỗi phát video: ' + error.message);
                    console.error('Video play error:', error);
                });
            }
        }
        
        function pauseVideo() {
            if (video) video.pause();
        }
        
        function reloadVideo() {
            if (video) {
                video.currentTime = 0;
                video.load();
            }
        }
        
        // Hiển thị thông tin video
        if (video) {
            video.addEventListener('loadeddata', function() {
                const info = `
                    <p>Độ dài: ${video.duration.toFixed(2)} giây</p>
                    <p>Độ phân giải: ${video.videoWidth} x ${video.videoHeight}</p>
                    <p>Đã tải: ${video.readyState >= 2 ? '✅' : '⏳'}</p>
                    <p>Trạng thái: ${video.paused ? 'Đang dừng' : 'Đang phát'}</p>
                `;
                document.getElementById('videoInfo').innerHTML = info;
            });
            
            video.addEventListener('error', function(e) {
                console.error('Video error:', video.error);
                document.getElementById('videoInfo').innerHTML = `
                    <p style="color: red;">❌ Lỗi video!</p>
                    <p>Code: ${video.error?.code || 'unknown'}</p>
                    <p>Message: ${video.error?.message || 'unknown'}</p>
                `;
            });
        }
        
        // Auto play after page load
        window.addEventListener('load', function() {
            setTimeout(() => {
                if (video && !video.paused) return;
                playVideo();
            }, 1000);
        });
    </script>
</body>
</html>
<?php
// Log thông tin
error_log("Camera test accessed: " . $camera['name']);
error_log("Video path: " . $video_path);
error_log("Video exists: " . ($video_exists ? 'yes' : 'no'));
?>