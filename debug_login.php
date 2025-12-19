<?php
// Debug đăng nhập
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>DEBUG ĐĂNG NHẬP</h2>";

// Kết nối database trực tiếp
try {
    $pdo = new PDO('mysql:host=localhost;dbname=traffic_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $phone = '0901234567';
    $password = 'csgtvn123';
    
    echo "<h3>1. Kiểm tra officer trong database</h3>";
    
    $sql = "SELECT * FROM officers WHERE so_dien_thoai = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$phone]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$officer) {
        echo "❌ KHÔNG TÌM THẤY officer với số điện thoại: $phone<br>";
        die();
    }
    
    echo "✅ Tìm thấy officer: <strong>" . $officer['ho_ten'] . "</strong><br>";
    echo "ID: " . $officer['id'] . "<br>";
    echo "Trạng thái (trang_thai): " . $officer['trang_thai'] . "<br>";
    echo "mat_khau_hien_thi: <strong>" . $officer['mat_khau_hien_thi'] . "</strong><br>";
    echo "mat_khau (hash): " . substr($officer['mat_khau'], 0, 30) . "...<br>";
    
    echo "<h3>2. Kiểm tra so sánh mật khẩu</h3>";
    echo "Mật khẩu nhập: '$password'<br>";
    echo "Mật khẩu trong DB: '" . $officer['mat_khau_hien_thi'] . "'<br>";
    
    $exactMatch = ($password === $officer['mat_khau_hien_thi']);
    $looseMatch = ($password == $officer['mat_khau_hien_thi']);
    
    echo "So sánh chặt (===): " . ($exactMatch ? "✅ ĐÚNG" : "❌ SAI") . "<br>";
    echo "So sánh lỏng (==): " . ($looseMatch ? "✅ ĐÚNG" : "❌ SAI") . "<br>";
    
    // Kiểm tra khoảng trắng
    echo "<h3>3. Kiểm tra khoảng trắng ẩn</h3>";
    echo "Độ dài mật khẩu nhập: " . strlen($password) . "<br>";
    echo "Độ dài mat_khau_hien_thi: " . strlen($officer['mat_khau_hien_thi']) . "<br>";
    
    // Hiển thị mã ASCII
    echo "Mã ASCII mat_khau_hien_thi: ";
    for ($i = 0; $i < strlen($officer['mat_khau_hien_thi']); $i++) {
        echo ord($officer['mat_khau_hien_thi'][$i]) . " ";
    }
    echo "<br>";
    
    echo "<h3>4. Kiểm tra bằng SQL trực tiếp</h3>";
    $sql = "SELECT 
                id,
                ho_ten,
                so_dien_thoai,
                mat_khau_hien_thi,
                LENGTH(mat_khau_hien_thi) as length,
                HEX(mat_khau_hien_thi) as hex_value
            FROM officers 
            WHERE so_dien_thoai = ? 
            AND mat_khau_hien_thi = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$phone, $password]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ SQL tìm thấy với mật khẩu chính xác!<br>";
        print_r($result);
    } else {
        echo "❌ SQL KHÔNG tìm thấy với mật khẩu này<br>";
        
        // Thử tìm tất cả
        $sql = "SELECT so_dien_thoai, mat_khau_hien_thi FROM officers";
        $stmt = $pdo->query($sql);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Tất cả mật khẩu trong DB:</h4>";
        foreach ($all as $row) {
            echo $row['so_dien_thoai'] . " => '" . $row['mat_khau_hien_thi'] . "' (length: " . strlen($row['mat_khau_hien_thi']) . ")<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "Lỗi database: " . $e->getMessage();
}
?>