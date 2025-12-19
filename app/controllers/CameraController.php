
<?php
session_start();

// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

class CameraController {
    
    private $cameras = [
        [
            'id' => 1,
            'name' => 'Camera Nguyễn Tất Thành',
            'location' => 'Ngã tư Nguyễn Tất Thành - Quận Liên Chiểu TP Đà Nẵng',
            'video_file' => 'nguyen-tat-thanh.webm',
            'image_files' => ['violation_1.jpg', 'violation_2.jpg'],
            'status' => 'online'
        ],
        [
            'id' => 2,
            'name' => 'Camera Điện Biên Phủ',
            'location' => 'Ngă tư Điện Biên Phủ - Q. Hải Châu, Thành Phố Đà Nẵng',
            'video_file' => 'dien-bien-phu.webm',
            'image_files' => ['violation_3.jpg'],
            'status' => 'online'
        ]
    ];
    
    private $violation_images = [
        1 => [
            [
                'id' => 1,
                'license_plate' => 'Không xác định',
                'timestamp' => '14:25:30',
                'image' => 'violation_1.jpg',
                'violation_type' => 'Dừng xe sai vị trí khi dừng đèn đỏ',
                'fine_amount' => 400000
            ],
            [
                'id' => 2,
                'license_plate' => 'Không xác định',
                'timestamp' => '14:20:15',
                'image' => 'violation_2.jpg',
                'violation_type' => 'Dừng xe sai vị trí khi dừng đèn đỏ',
                'fine_amount' => 400000
            ]
        ],
        2 => [
            [
                'id' => 3,
                'license_plate' => '43C-54321',
                'timestamp' => '14:18:45',
                'image' => 'violation_3.jpg',
                'violation_type' => 'Quá tốc độ',
                'fine_amount' => 1500000
            ]
        ]
    ];
    
    public function __construct() {
        $this->checkAuth();
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['officer_id'])) {
            $_SESSION['error'] = "Vui lòng đăng nhập";
            header('Location: /traffic/app/views/officers/login.php');
            exit();
        }
    }
    
    // Trang danh sách camera
    public function index() {
        $data = [
            'cameras' => $this->cameras,
            'officer_name' => $_SESSION['officer_name'] ?? 'Cán bộ'
        ];
        
        $this->loadView('camera/index.php', $data);
    }
    
    // Xem camera cụ thể
    public function view($cameraId) {
        // Tìm camera
        $camera = null;
        foreach ($this->cameras as $cam) {
            if ($cam['id'] == $cameraId) {
                $camera = $cam;
                break;
            }
        }
        
        if (!$camera) {
            $_SESSION['error'] = "Không tìm thấy camera";
            header('Location: /traffic/app/controllers/CameraController.php?action=index');
            exit();
        }
        
        // Đường dẫn video
        $video_url = '/traffic/public/assets/videos/' . $camera['video_file'];
        $video_path = $_SERVER['DOCUMENT_ROOT'] . $video_url;
        
        // Kiểm tra file tồn tại
        $video_exists = file_exists($video_path);
        
        // Lấy thông tin video
        $video_info = [];
        if ($video_exists) {
            $video_size = filesize($video_path);
            $video_info['size_mb'] = round($video_size / (1024 * 1024), 2);
            $video_info['path'] = $video_path;
        }
        
        $data = [
            'camera' => $camera,
            'violation_images' => $this->violation_images[$cameraId] ?? [],
            'officer_name' => $_SESSION['officer_name'] ?? 'Cán bộ',
            'video_exists' => $video_exists,
            'video_info' => $video_info,
            'video_url' => $video_url,
            'violation_count' => count($this->violation_images[$cameraId] ?? []),
            'total_fine' => $this->calculateTotalFine($cameraId)
        ];
        
        $this->loadView('camera/view.php', $data);
    }
    
    private function calculateTotalFine($cameraId) {
        $total = 0;
        if (isset($this->violation_images[$cameraId])) {
            foreach ($this->violation_images[$cameraId] as $violation) {
                $total += $violation['fine_amount'];
            }
        }
        return number_format($total);
    }
    
    // Xử lý xác nhận vi phạm
    public function confirmViolation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Phương thức không hợp lệ";
            header('Location: /traffic/app/controllers/CameraController.php?action=index');
            exit();
        }
        
        // Lấy dữ liệu
        $camera_id = $_POST['camera_id'] ?? 0;
        $license_plate = strtoupper(trim($_POST['license_plate'] ?? ''));
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $violation_type = $_POST['violation_type'] ?? '';
        $fine_amount = $_POST['fine_amount'] ?? 0;
        
        // Validate đơn giản
        if (empty($license_plate) || empty($vehicle_type) || empty($violation_type)) {
            $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin";
            header('Location: /traffic/app/controllers/CameraController.php?action=view&id=' . $camera_id);
            exit();
        }
        
        // Lưu dữ liệu vào session
        $_SESSION['camera_violation_data'] = [
            'camera_id' => $camera_id,
            'license_plate' => $license_plate,
            'vehicle_type' => $vehicle_type,
            'violation_type' => $violation_type,
            'fine_amount' => $fine_amount,
            'violation_time' => $_POST['violation_time'] ?? date('H:i'),
            'violation_date' => $_POST['violation_date'] ?? date('Y-m-d'),
            'description' => $_POST['description'] ?? '',
            'officer_name' => $_SESSION['officer_name'] ?? 'Cán bộ'
        ];
        
        // Chuyển hướng đến form tạo biên bản
        header('Location: /traffic/app/views/officers/camera_violation_form.php');
        exit();
    }
    
    private function loadView($view, $data = []) {
        extract($data);
        $viewPath = $_SERVER['DOCUMENT_ROOT'] . '/traffic/app/views/' . $view;
        
        if (!file_exists($viewPath)) {
            die("View file not found: " . $view);
        }
        
        include $viewPath;
    }
}

// Router
if (isset($_GET['action'])) {
    $controller = new CameraController();
    
    switch ($_GET['action']) {
        case 'index':
            $controller->index();
            break;
        case 'view':
            $id = $_GET['id'] ?? 0;
            $controller->view($id);
            break;
        case 'confirm':
            $controller->confirmViolation();
            break;
        default:
            $controller->index();
            break;
    }
}
?>
