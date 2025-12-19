
<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Kiểm tra đăng nhập đơn giản
if (!isset($_SESSION['officer_id'])) {
    // Mock session cho testing
    $_SESSION['officer_id'] = 1;
    $_SESSION['officer_name'] = 'Cán bộ Test';
}

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

// Xác định action
$action = $_GET['action'] ?? 'index';

// Router đơn giản
switch ($action) {
    case 'index':
        showCameraList($cameras);
        break;
    case 'view':
        $id = $_GET['id'] ?? 1;
        showCameraView($id, $cameras);
        break;
    default:
        showCameraList($cameras);
        break;
}

function showCameraList($cameras) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Camera List</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            .camera { border: 1px solid #ccc; padding: 15px; margin: 10px; border-radius: 5px; }
            .btn { background: blue; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <h1>Danh sách Camera</h1>
        <?php foreach ($cameras as $camera): ?>
        <div class="camera">
            <h3><?php echo htmlspecialchars($camera['name']); ?></h3>
            <p><?php echo htmlspecialchars($camera['location']); ?></p>
            <a href="CameraController_simple.php?action=view&id=<?php echo $camera['id']; ?>" class="btn">
                Xem Camera
            </a>
        </div>
        <?php endforeach; ?>
        <p><a href="/traffic/app/controllers/OfficerController.php?action=dashboard">← Quay lại Dashboard</a></p>
    </body>
    </html>
    <?php
}

function showCameraView($id, $cameras) {
    $camera = null;
    foreach ($cameras as $cam) {
        if ($cam['id'] == $id) {
            $camera = $cam;
            break;
        }
    }
    
    if (!$camera) {
        echo "Camera không tồn tại";
        return;
    }
    
    $video_url = '/traffic/public/assets/videos/' . $camera['video_file'];
    $video_path = $_SERVER['DOCUMENT_ROOT'] . $video_url;
    $video_exists = file_exists($video_path);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo htmlspecialchars($camera['name']); ?></title>
        <style>
            body { font-family: Arial; padding: 20px; }
            video { max-width: 100%; height: auto; border: 2px solid #333; }
            .info { background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1><?php echo htmlspecialchars($camera['name']); ?></h1>
        
        <div class="info">
            <p><strong>Vị trí:</strong> <?php echo htmlspecialchars($camera['location']); ?></p>
            <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($camera['status']); ?></p>
            <p><strong>Video tồn tại:</strong> <?php echo $video_exists ? '✅ Có' : '❌ Không'; ?></p>
            <?php if ($video_exists): ?>
            <p><strong>Kích thước:</strong> <?php echo number_format(filesize($video_path)); ?> bytes</p>
            <?php endif; ?>
        </div>
        
        <?php if ($video_exists): ?>
        <div>
            <h2>Video Trực Tiếp</h2>
            <video controls width="800">
                <source src="<?php echo htmlspecialchars($video_url); ?>" type="video/mp4">
                Trình duyệt không hỗ trợ video
            </video>
        </div>
        <?php else: ?>
        <div style="background: #ffe6e6; padding: 15px; border-radius: 5px;">
            <p>⚠️ Video không tồn tại tại: <?php echo htmlspecialchars($video_path); ?></p>
            <p>Kiểm tra đường dẫn file video</p>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="CameraController_simple.php?action=index">← Quay lại danh sách</a>
        </div>
    </body>
    </html>
    <?php
}
?>
