
<?php
session_start();

// Giả lập session
$_SESSION['officer_id'] = 1;
$_SESSION['officer_name'] = 'Nguyễn Văn A';
$_SESSION['officer_unit'] = 'Đội CSGT';

// Dữ liệu camera đơn giản
$camera = [
    'id' => 1,
    'name' => 'Camera Nguyễn Tất Thành',
    'location' => 'Ngã tư Nguyễn Tất Thành - Lê Duẩn, Quận 1',
    'video_file' => 'nguyen-tat-thanh.webm',
    'image_files' => ['violation_1.jpg', 'violation_2.jpg'],
    'status' => 'online'
];

// Đường dẫn
$video_url = '/traffic/public/assets/videos/' . $camera['video_file'];
$video_path = $_SERVER['DOCUMENT_ROOT'] . $video_url;
$video_exists = file_exists($video_path);

// Kiểm tra lỗi
echo "<pre>";
echo "=== DEBUG INFO ===\n";
echo "Video URL: $video_url\n";
echo "Video Path: $video_path\n";
echo "Video Exists: " . ($video_exists ? 'YES' : 'NO') . "\n";
echo "Session: officer_id=" . ($_SESSION['officer_id'] ?? 'NULL') . "\n";

if (!$video_exists) {
    echo "ERROR: Video file not found at: $video_path\n";
    // Kiểm tra các file xung quanh
    $dir = dirname($video_path);
    echo "Directory contents:\n";
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    }
}
echo "</pre>";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Camera Simple</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: white; font-family: sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .video-container { background: black; border-radius: 10px; overflow: hidden; }
        video { width: 100%; }
        .status { padding: 10px; background: <?php echo $video_exists ? '#059669' : '#dc2626'; ?>; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($camera['name']); ?></h1>
        
        <div class="video-container">
            <div class="status">
                <i class="fas fa-<?php echo $video_exists ? 'circle-check' : 'circle-xmark'; ?>"></i>
                <?php echo $video_exists ? 'VIDEO READY' : 'VIDEO NOT FOUND'; ?>
            </div>
            
            <?php if ($video_exists): ?>
            <video controls>
                <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/webm">
                Your browser does not support the video tag.
            </video>
            <?php else: ?>
            <div style="padding: 50px; text-align: center; background: #1e293b;">
                <i class="fas fa-video-slash" style="font-size: 48px; color: #64748b;"></i>
                <p>Video file not found</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #1e293b; border-radius: 8px;">
            <h3>Ảnh vi phạm:</h3>
            <div style="display: flex; gap: 10px;">
                <?php foreach ($camera['image_files'] as $image): 
                    $img_url = '/traffic/public/assets/images/' . $image;
                    $img_path = $_SERVER['DOCUMENT_ROOT'] . $img_url;
                    $img_exists = file_exists($img_path);
                ?>
                <div style="width: 200px;">
                    <?php if ($img_exists): ?>
                    <img src="<?php echo htmlspecialchars($img_url); ?>" 
                         style="width: 100%; height: 150px; object-fit: cover; border-radius: 5px;">
                    <?php else: ?>
                    <div style="width: 100%; height: 150px; background: #334155; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                        <i class="fas fa-image" style="color: #64748b;"></i>
                    </div>
                    <?php endif; ?>
                    <p style="font-size: 12px; margin-top: 5px;">
                        <?php echo htmlspecialchars($image); ?>
                        <?php if ($img_exists): ?>
                        <span style="color: #10b981;">✓</span>
                        <?php else: ?>
                        <span style="color: #ef4444;">✗</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
