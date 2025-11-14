<?php
class AuthController {
    // Hiển thị trang đăng nhập
    public function showOfficerLogin() {
        // Include trang login
        require_once 'app/views/officers/login.php';
    }
    
    // Xử lý đăng nhập (POST)
    public function processOfficerLogin() {
        // Xử lý logic đăng nhập ở đây
        if ($_POST['phone'] && $_POST['password']) {
            // Kiểm tra thông tin đăng nhập
            // ...
            
            // Nếu thành công, chuyển hướng đến dashboard
            header('Location: /officers/dashboard');
            exit;
        }
    }
}
?>