<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG ĐĂNG NHẬP CHI TIẾT</h2>";

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>1. Xử lý POST request</h3>";
    
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    echo "Phone nhận được: '$phone'<br>";
    echo "Password nhận được: '$password'<br>";
    echo "Remember: " . ($remember ? 'YES' : 'NO') . "<br>";
    
    // Xóa khoảng trắng
    $phone = preg_replace('/\s+/', '', $phone);
    echo "Phone sau khi xóa khoảng trắng: '$phone'<br>";
    
    if (empty($phone) || empty($password)) {
        echo "❌ Lỗi: Số điện thoại hoặc mật khẩu trống<br>";
        $_SESSION['error'] = 'Vui lòng nhập đầy đủ số điện thoại và mật khẩu';
    } else {
        echo "<h3>2. Kết nối database</h3>";
        
        try {
            // Kết nối database TRỰC TIẾP
            $pdo = new PDO('mysql:host=localhost;dbname=traffic_db;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "✅ Kết nối database thành công<br>";
            
            echo "<h3>3. Tìm officer</h3>";
            
            $sql = "SELECT 
                        id,
                        ma_can_bo,
                        ho_ten as full_name,
                        so_dien_thoai as phone,
                        mat_khau_hien_thi as password,
                        cap_bac as rank,
                        don_vi as unit,
                        email,
                        trang_thai as status
                    FROM officers 
                    WHERE so_dien_thoai = ? 
                    AND trang_thai = 1 
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$phone]);
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$officer) {
                echo "❌ KHÔNG tìm thấy officer với số điện thoại: $phone<br>";
                $_SESSION['error'] = 'Số điện thoại hoặc mật khẩu không đúng';
            } else {
                echo "✅ Tìm thấy officer: " . $officer['full_name'] . "<br>";
                echo "   Password trong DB: '" . $officer['password'] . "'<br>";
                echo "   Password nhập vào: '$password'<br>";
                
                // So sánh mật khẩu
                $passwordMatch = ($password === $officer['password']);
                echo "   So sánh === : " . ($passwordMatch ? '✅ ĐÚNG' : '❌ SAI') . "<br>";
                
                if (!$passwordMatch) {
                    echo "   ❌ Mật khẩu không khớp!<br>";
                    $_SESSION['error'] = 'Số điện thoại hoặc mật khẩu không đúng';
                } else {
                    echo "✅ Mật khẩu ĐÚNG!<br>";
                    
                    // Kiểm tra trạng thái
                    if ($officer['status'] == 0) {
                        echo "❌ Tài khoản bị khóa (status = 0)<br>";
                        $_SESSION['error'] = 'Tài khoản đã bị khóa';
                    } else {
                        echo "<h3>4. Tạo session</h3>";
                        
                        // Tạo session
                        $_SESSION['officer_id'] = $officer['id'];
                        $_SESSION['officer_name'] = $officer['full_name'];
                        $_SESSION['officer_rank'] = $officer['rank'];
                        $_SESSION['officer_unit'] = $officer['unit'];
                        $_SESSION['officer_phone'] = $officer['phone'];
                        $_SESSION['officer_email'] = $officer['email'];
                        
                        echo "✅ Session created:<br>";
                        echo "   officer_id: " . $_SESSION['officer_id'] . "<br>";
                        echo "   officer_name: " . $_SESSION['officer_name'] . "<br>";
                        
                        // Remember me
                        if ($remember) {
                            setcookie('csgt_remembered_phone', $phone, time() + (30 * 24 * 60 * 60), '/');
                            echo "✅ Đã set cookie remember<br>";
                        }
                        
                        // Ghi log đăng nhập
                        try {
                            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                            $logSql = "INSERT INTO login_logs (officer_id, ip_address, login_time) 
                                       VALUES (?, ?, NOW())";
                            $logStmt = $pdo->prepare($logSql);
                            $logStmt->execute([$officer['id'], $ip]);
                            echo "✅ Đã ghi log đăng nhập<br>";
                        } catch (Exception $e) {
                            echo "⚠️ Không ghi được log: " . $e->getMessage() . "<br>";
                        }
                        
                        // Cập nhật last_login
                        try {
                            $updateSql = "UPDATE officers SET last_login = NOW() WHERE id = ?";
                            $updateStmt = $pdo->prepare($updateSql);
                            $updateStmt->execute([$officer['id']]);
                            echo "✅ Đã cập nhật last_login<br>";
                        } catch (Exception $e) {
                            echo "⚠️ Không cập nhật được last_login<br>";
                        }
                        
                        echo "<h3>5. Redirect đến dashboard</h3>";
                        echo "Redirecting to: /traffic/app/views/officers/dashboard.php<br>";
                        
                        // Thử redirect
                        header('Location: /traffic/app/views/officers/dashboard.php');
                        exit();
                    }
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
            $_SESSION['error'] = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
    
    // Nếu có lỗi, show ra
    if (isset($_SESSION['error'])) {
        echo "<h3>❌ CÓ LỖI</h3>";
        echo "Lỗi: " . $_SESSION['error'] . "<br>";
    }
} else {
    echo "<h3>⚠️ Đây là GET request, không phải POST</h3>";
    echo "Hãy submit form để test đăng nhập<br>";
}

// Hiển thị form đăng nhập đơn giản
?>
<h2>FORM ĐĂNG NHẬP TEST</h2>
<form method="POST" action="">
    <div>
        <label>Số điện thoại:</label>
        <input type="tel" name="phone" value="0901234567" required>
    </div>
    <div>
        <label>Mật khẩu:</label>
        <input type="password" name="password" value="csgtvn123" required>
    </div>
    <div>
        <label>
            <input type="checkbox" name="remember"> Nhớ đăng nhập
        </label>
    </div>
    <button type="submit">Đăng nhập TEST</button>
</form>

<hr>
<h3>Kiểm tra session hiện tại:</h3>
<?php
echo "Session ID: " . session_id() . "<br>";
echo "Session data: ";
print_r($_SESSION);
?>