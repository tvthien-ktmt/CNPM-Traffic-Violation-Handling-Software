
<?php
// Test video trực tiếp - không cần session
$videos = [
    'Nguyễn Tất Thành' => 'nguyen-tat-thanh.mp4',
    'Điện Biên Phủ' => 'dien-bien-phu.mp4'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Video Direct</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .video-test { margin: 20px 0; padding: 15px; border: 2px solid #ccc; border-radius: 10px; }
        video { max-width: 800px; width: 100%; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Video Trực Tiếp</h1>
    <p>Kiểm tra xem video có phát được không</p>
    
    <?php foreach ($videos as $name => $file): 
        $path = $_SERVER['DOCUMENT_ROOT'] . '/traffic/public/assets/videos/' . $file;
        $url = '/traffic/public/assets/videos/' . $file;
        $exists = file_exists($path);
    ?>
    <div class="video-test">
        <h3><?php echo $name; ?> (<?php echo $file; ?>)</h3>
        <p>Tồn tại: 
            <?php if ($exists): ?>
                <span class="success">✅ CÓ (<?php echo number_format(filesize($path)); ?> bytes)</span>
            <?php else: ?>
                <span class="error">❌ KHÔNG</span>
            <?php endif; ?>
        </p>
        
        <?php if ($exists): ?>
        <div>
            <video controls>
                <source src="<?php echo htmlspecialchars($url); ?>" type="video/mp4">
            </video>
            <p><a href="<?php echo htmlspecialchars($url); ?>" target="_blank">Mở video riêng</a></p>
        </div>
        <?php else: ?>
        <p style="color: red;">File không tồn tại: <?php echo htmlspecialchars($path); ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <hr>
    <h3>Các bước test:</h3>
    <ol>
        <li>Click vào link "Mở video riêng" - nếu video phát được: OK</li>
        <li>Click play trên video trong trang - nếu phát được: OK</li>
        <li>Nếu không phát được: Kiểm tra tên file và đường dẫn</li>
    </ol>
    
    <p><a href="/traffic/app/controllers/CameraController.php?action=index">Vào hệ thống camera</a></p>
</body>
</html>
