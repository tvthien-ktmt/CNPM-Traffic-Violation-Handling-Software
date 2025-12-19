<?php
// File: check_database.php
require_once 'config/database.php';

echo "<h3>🔍 KIỂM TRA DATABASE</h3>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<p style='color:green'>✅ Kết nối database thành công</p>";
    
    // 1. Kiểm tra bảng
    $tables = ['violations', 'violation_types', 'users', 'vehicles'];
    
    echo "<h4>📊 Kiểm tra bảng:</h4>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ Bảng '$table' tồn tại</p>";
            
            // Đếm số bản ghi
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $countStmt->fetch()['count'];
            echo "<p style='margin-left:20px'>Số bản ghi: $count</p>";
            
            // Hiển thị vài bản ghi mẫu
            if ($count > 0 && $table == 'violations') {
                $sampleStmt = $pdo->query("SELECT bien_so, trang_thai, muc_phat FROM $table LIMIT 5");
                $samples = $sampleStmt->fetchAll();
                
                echo "<p style='margin-left:20px'>Mẫu dữ liệu:</p>";
                echo "<table border='1' style='margin-left:20px'>";
                echo "<tr><th>Biển số</th><th>Trạng thái</th><th>Mức phạt</th></tr>";
                foreach ($samples as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['bien_so']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['trang_thai']) . "</td>";
                    echo "<td>" . number_format($row['muc_phat']) . " VND</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color:red'>❌ Bảng '$table' KHÔNG tồn tại</p>";
        }
    }
    
    // 2. Test tra cứu biển số
    echo "<h4>🔎 Test tra cứu biển số:</h4>";
    
    $testPlates = ['29T124327', '36B778195', '29BH09024'];
    
    foreach ($testPlates as $plate) {
        $cleanPlate = strtoupper(str_replace(['-', '.', ' '], '', $plate));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM violations 
            WHERE bien_so = ? AND trang_thai = 'Chưa xử lý'
        ");
        $stmt->execute([$cleanPlate]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "<p style='color:green'>✅ Tìm thấy {$result['count']} vi phạm cho biển số: $plate</p>";
            
            // Hiển thị chi tiết
            $detailStmt = $pdo->prepare("
                SELECT v.*, vt.ten_loi 
                FROM violations v
                LEFT JOIN violation_types vt ON v.violation_type_id = vt.id
                WHERE v.bien_so = ? AND v.trang_thai = 'Chưa xử lý'
            ");
            $detailStmt->execute([$cleanPlate]);
            $details = $detailStmt->fetchAll();
            
            foreach ($details as $detail) {
                echo "<div style='margin-left:20px; background:#f0f0f0; padding:10px; margin-bottom:5px'>";
                echo "ID: {$detail['id']}<br>";
                echo "Loại: {$detail['ten_loi']}<br>";
                echo "Thời gian: {$detail['thoi_gian_vi_pham']}<br>";
                echo "Mức phạt: " . number_format($detail['muc_phat']) . " VND<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Không tìm thấy vi phạm cho: $plate</p>";
        }
    }
    
    // 3. Kiểm tra dữ liệu import
    echo "<h4>📈 Thống kê:</h4>";
    
    $stats = [
        "Tổng vi phạm" => "SELECT COUNT(*) as count FROM violations",
        "Vi phạm chưa xử lý" => "SELECT COUNT(*) as count FROM violations WHERE trang_thai = 'Chưa xử lý'",
        "Vi phạm đã thanh toán" => "SELECT COUNT(*) as count FROM violations WHERE trang_thai = 'Đã thanh toán'",
        "Biển số duy nhất" => "SELECT COUNT(DISTINCT bien_so) as count FROM violations",
        "Tổng tiền phạt chưa xử lý" => "SELECT SUM(muc_phat) as total FROM violations WHERE trang_thai = 'Chưa xử lý'"
    ];
    
    foreach ($stats as $label => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch();
        echo "<p>$label: <strong>" . ($result['count'] ?? $result['total'] ?? 0) . "</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>