<?php
session_start();

class AuthController {
    private $db;

    public function __construct() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/config/database.php';
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
    }

    // Xử lý đăng nhập
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // DEBUG: Log the request
            error_log("=== LOGIN ATTEMPT ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("Server: " . $_SERVER['HTTP_HOST']);
            
            $so_dien_thoai = $_POST['so_dien_thoai'] ?? '';
            $mat_khau = $_POST['mat_khau'] ?? '';
            
            // Clean phone number
            $so_dien_thoai = preg_replace('/[^0-9]/', '', $so_dien_thoai);
            
            error_log("Phone (cleaned): $so_dien_thoai");
            error_log("Password length: " . strlen($mat_khau));
            
            // Validate input
            if (empty($so_dien_thoai) || empty($mat_khau)) {
                $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin";
                error_log("Validation failed: empty fields");
                $this->redirectToLogin();
            }
            
            // Kiểm tra thông tin đăng nhập
            $sql = "SELECT * FROM officers WHERE so_dien_thoai = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$so_dien_thoai]);
            
            if ($stmt->rowCount() > 0) {
                $officer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Officer found: " . $officer['ho_ten']);
                error_log("DB Password hash: " . substr($officer['mat_khau'], 0, 30) . "...");
                
                // Kiểm tra mật khẩu (hash bcrypt)
                if (password_verify($mat_khau, $officer['mat_khau'])) {
                    error_log("✅ Password CORRECT");
                    
                    // Lưu thông tin cán bộ vào session
                    $_SESSION['officer_id'] = $officer['id'];
                    $_SESSION['ho_ten'] = $officer['ho_ten'];
                    $_SESSION['cap_bac'] = $officer['cap_bac'];
                    $_SESSION['don_vi'] = $officer['don_vi'];
                    $_SESSION['ma_can_bo'] = $officer['ma_can_bo'];
                    $_SESSION['email'] = $officer['email'];
                    $_SESSION['so_dien_thoai'] = $officer['so_dien_thoai'];
                    
                    // Remember me
                    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                        setcookie('csgt_remembered_phone', $so_dien_thoai, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    error_log("✅ Login SUCCESS, redirecting to dashboard");
                    
                    // Chuyển hướng đến trang dashboard
                    header('Location: /traffic/app/views/officers/dashboard.php');
                    exit();
                } else {
                    error_log("❌ Password WRONG");
                    $_SESSION['error'] = "Sai mật khẩu!";
                    $this->redirectToLogin();
                }
            } else {
                error_log("❌ Officer NOT FOUND: $so_dien_thoai");
                $_SESSION['error'] = "Số điện thoại không tồn tại!";
                $this->redirectToLogin();
            }
        }
        
        // If not POST, redirect to login
        $this->redirectToLogin();
    }

    // Đăng xuất
    public function logout() {
        session_destroy();
        setcookie('csgt_remembered_phone', '', time() - 3600, '/');
        header('Location: /traffic/app/views/officers/login.php');
        exit();
    }

    // Kiểm tra đăng nhập
    public static function checkAuth() {
        if (!isset($_SESSION['officer_id'])) {
            header('Location: /traffic/app/views/officers/login.php');
            exit();
        }
    }
    
    // Helper function to redirect to login
    private function redirectToLogin() {
        header('Location: /traffic/app/views/officers/login.php');
        exit();
    }
}

// Router đơn giản
if (isset($_GET['action'])) {
    $auth = new AuthController();
    
    switch ($_GET['action']) {
        case 'login':
            $auth->login();
            break;
        case 'logout':
            $auth->logout();
            break;
        default:
            header('Location: /traffic/app/views/officers/login.php');
            break;
    }
} else {
    // Default redirect
    header('Location: /traffic/app/views/officers/login.php');
    exit();
}
?>