<?php
// Kiểm tra trạng thái officer
try {
    $pdo = new PDO('mysql:host=localhost;dbname=traffic_db;charset=utf8mb4', 'root', '');
    
    echo "<h2>KIỂM TRA TRẠNG THÁI OFFICER</h2>";
    
    $phone = '0901234567';
    
    // Kiểm tra không có điều kiện status
    $sql = "SELECT id, ho_ten, so_dien_thoai, trang_thai FROM officers WHERE so_dien_thoai = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$phone]);
    $officer = $stmt->fetch();
    
    if ($officer) {
        echo "✅ Tìm thấy officer: " . $officer['ho_ten'] . "<br>";
        echo "   trang_thai: " . ($officer['trang_thai'] === null ? 'NULL' : $officer['trang_thai']) . "<br>";
        echo "   Kiểu dữ liệu: " . gettype($officer['trang_thai']) . "<br>";
        
        // Hiển thị tất cả trạng thái
        $sql2 = "SELECT so_dien_thoai, ho_ten, trang_thai FROM officers";
        $stmt2 = $pdo->query($sql2);
        $all = $stmt2->fetchAll();
        
        echo "<h3>Tất cả officers:</h3>";
        foreach ($all as $row) {
            echo $row['so_dien_thoai'] . " - " . $row['ho_ten'] . " - trang_thai: " . 
                 ($row['trang_thai'] === null ? 'NULL' : $row['trang_thai']) . "<br>";
        }
    } else {
        echo "❌ Không tìm thấy officer<br>";
    }
    
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>