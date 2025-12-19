<?php
/**
 * Script Import dữ liệu vi phạm từ JSON vào Database
 * File: scripts/import_violations_from_json.php
 */

require_once __DIR__ . '/../config/database.php';

class ViolationImporter {
    private $db;
    private $jsonFiles = [
        __DIR__ . '/../app/views/violations/data_xuphat/data_13-14_11_2025.json',
        __DIR__ . '/../app/views/violations/data_xuphat/data_19_11_2025.json',
        __DIR__ . '/../app/views/violations/data_xuphat/data_19-20_11_2025.json',
        __DIR__ . '/../app/views/violations/data_xuphat/data_27-28_11_2025.json'
    ];
    
    // Mapping loại cảnh báo -> ID loại vi phạm trong database
    private $violationTypeMapping = [
        'Vượt đèn đỏ' => 1,
        'Không đội mũ' => 2,
        'Không thắt dây đai an toàn' => 3,
        'Vượt quá tốc độ' => 4,
        'Sử dụng điện thoại khi lái xe' => 5,
        'Dừng đỗ xe sai quy định' => 6,
        'Chạy ngược chiều' => 7,
        'Chạy sai làn đường' => 10
    ];
    
    // Mapping loại xe
    private $vehicleTypeMapping = [
        'Xe mô tô' => 'Xe mô tô',
        'Ô tô con' => 'Ô tô con',
        'Ô tô tải từ 3,5 tấn' => 'Ô tô tải từ 3,5 tấn',
        'Những loại khác' => 'Những loại khác'
    ];
    
    public function __construct() {
        //  SỬA: Dùng getInstance() thay vì new Database()
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tạo số CCCD ngẫu nhiên
     */
    private function generateCCCD() {
        return '0' . str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
    }
    
    /**
     * Tạo số điện thoại ngẫu nhiên
     */
    private function generatePhone() {
        $prefixes = ['091', '090', '093', '094', '096', '097', '098', '086', '088'];
        return $prefixes[array_rand($prefixes)] . rand(1000000, 9999999);
    }
    
    /**
     * Tạo tên ngẫu nhiên
     */
    private function generateRandomName() {
        $ho = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Võ', 'Phan', 'Vũ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương'];
        $tenDem = ['Văn', 'Thị', 'Hữu', 'Đức', 'Minh', 'Thanh', 'Quang', 'Hồng', 'Phương', 'Anh'];
        $ten = ['An', 'Bình', 'Cường', 'Dũng', 'Hà', 'Hùng', 'Khoa', 'Long', 'Nam', 'Phong', 'Tân', 'Tùng', 'Linh', 'Mai', 'Nga', 'Thảo'];
        
        return $ho[array_rand($ho)] . ' ' . $tenDem[array_rand($tenDem)] . ' ' . $ten[array_rand($ten)];
    }
    
    /**
     * Tìm hoặc tạo user theo biển số
     */
    private function findOrCreateUser($bienSo) {
        // Kiểm tra xem đã có user với biển số này chưa
        $stmt = $this->db->prepare("
            SELECT u.id, u.cccd, u.so_dien_thoai 
            FROM users u 
            INNER JOIN vehicles v ON u.id = v.user_id 
            WHERE v.bien_so = ?
        ");
        $stmt->execute([$bienSo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return $user;
        }
        
        // Tạo user mới
        $hoTen = $this->generateRandomName();
        $cccd = $this->generateCCCD();
        $soDienThoai = $this->generatePhone();
        $email = $this->slugify($hoTen) . rand(100, 999) . '@email.com';
        $matKhau = password_hash('123456', PASSWORD_DEFAULT); // Mật khẩu mặc định
        
        $stmt = $this->db->prepare("
            INSERT INTO users (ho_ten, cccd, email, so_dien_thoai, mat_khau, dia_chi, ngay_sinh) 
            VALUES (?, ?, ?, ?, ?, 'Hà Nội', ?)
        ");
        
        $ngaySinh = date('Y-m-d', strtotime('-' . rand(25, 60) . ' years'));
        $stmt->execute([$hoTen, $cccd, $email, $soDienThoai, $matKhau, $ngaySinh]);
        
        return [
            'id' => $this->db->lastInsertId(),
            'cccd' => $cccd,
            'so_dien_thoai' => $soDienThoai
        ];
    }
    
    /**
     * Tìm hoặc tạo vehicle
     */
    private function findOrCreateVehicle($userId, $bienSo, $loaiXe, $mauBien) {
        // Chuẩn hóa loại xe
        $loaiXeMapped = $this->vehicleTypeMapping[$loaiXe] ?? 'Những loại khác';
        
        // Kiểm tra xem vehicle đã tồn tại chưa
        $stmt = $this->db->prepare("SELECT id FROM vehicles WHERE bien_so = ?");
        $stmt->execute([$bienSo]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle) {
            return $vehicle['id'];
        }
        
        // Tạo vehicle mới
        $hangXe = ['Honda', 'Yamaha', 'Toyota', 'Hyundai', 'Mazda', 'Ford', 'VinFast'];
        $mauXe = ['Đen', 'Trắng', 'Xanh', 'Đỏ', 'Bạc', 'Xám'];
        
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (user_id, bien_so, loai_xe, mau_bien, hang_xe, mau_xe, nam_san_xuat) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $bienSo,
            $loaiXeMapped,
            $mauBien,
            $hangXe[array_rand($hangXe)],
            $mauXe[array_rand($mauXe)],
            rand(2015, 2024)
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Lấy mức phạt từ loại vi phạm
     */
    private function getFineAmount($violationTypeId) {
        $stmt = $this->db->prepare("
            SELECT muc_phat_toi_thieu, muc_phat_toi_da 
            FROM violation_types 
            WHERE id = ?
        ");
        $stmt->execute([$violationTypeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Lấy mức phạt ngẫu nhiên trong khoảng
            return rand($result['muc_phat_toi_thieu'], $result['muc_phat_toi_da']);
        }
        
        return 500000; // Mặc định
    }
    
    /**
     * Chuyển chuỗi thành slug
     */
    private function slugify($text) {
        $text = strtolower($text);
        $text = preg_replace('/[áàảãạăắằẳẵặâấầẩẫậ]/u', 'a', $text);
        $text = preg_replace('/[éèẻẽẹêếềểễệ]/u', 'e', $text);
        $text = preg_replace('/[íìỉĩị]/u', 'i', $text);
        $text = preg_replace('/[óòỏõọôốồổỗộơớờởỡợ]/u', 'o', $text);
        $text = preg_replace('/[úùủũụưứừửữự]/u', 'u', $text);
        $text = preg_replace('/[ýỳỷỹỵ]/u', 'y', $text);
        $text = preg_replace('/đ/u', 'd', $text);
        $text = preg_replace('/[^a-z0-9]/', '', $text);
        return $text;
    }
    
    /**
     * Parse thời gian từ JSON
     */
    private function parseDateTime($timeString) {
        // Format: "27-11-2025 20:58:18" hoặc "19/11/2025 - 06:08:21"
        $timeString = str_replace(' - ', ' ', $timeString);
        $timeString = str_replace('/', '-', $timeString);
        
        try {
            $dt = DateTime::createFromFormat('d-m-Y H:i:s', $timeString);
            if ($dt) {
                return $dt->format('Y-m-d H:i:s');
            }
        } catch (Exception $e) {
            // Nếu parse lỗi, trả về thời gian hiện tại
            return date('Y-m-d H:i:s');
        }
        
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Import violations từ JSON
     */
    public function import() {
        $totalImported = 0;
        $totalSkipped = 0;
        
        echo "=== BẮT ĐẦU IMPORT DỮ LIỆU VI PHẠM ===\n\n";
        
        foreach ($this->jsonFiles as $jsonFile) {
            if (!file_exists($jsonFile)) {
                echo " File không tồn tại: $jsonFile\n";
                continue;
            }
            
            echo " Đang xử lý: " . basename($jsonFile) . "\n";
            
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            
            if (!isset($data['violations']) || !is_array($data['violations'])) {
                echo "  File không có dữ liệu vi phạm\n\n";
                continue;
            }
            
            foreach ($data['violations'] as $violation) {
                try {
                    $bienSo = strtoupper(str_replace(['-', '.', ' '], '', trim($violation['bien_so'] ?? '')));
                    $loaiCanhBao = $violation['loai_canh_bao'] ?? '';
                    $doiTuong = $violation['doi_tuong'] ?? '';
                    $mauBien = $violation['mau_bien'] ?? 'Trắng';
                    $thoiGian = $this->parseDateTime($violation['thoi_gian'] ?? '');
                    
                    // Bỏ qua nếu thiếu thông tin quan trọng
                    if (empty($bienSo) || empty($loaiCanhBao)) {
                        $totalSkipped++;
                        continue;
                    }
                    
                    // Kiểm tra vi phạm đã tồn tại chưa
                    $stmt = $this->db->prepare("
                        SELECT id FROM violations 
                        WHERE bien_so = ? AND thoi_gian_vi_pham = ?
                    ");
                    $stmt->execute([$bienSo, $thoiGian]);
                    if ($stmt->fetch()) {
                        $totalSkipped++;
                        continue; // Đã tồn tại, bỏ qua
                    }
                    
                    // Tìm loại vi phạm
                    $violationTypeId = null;
                    foreach ($this->violationTypeMapping as $key => $value) {
                        if (stripos($loaiCanhBao, $key) !== false) {
                            $violationTypeId = $value;
                            break;
                        }
                    }
                    
                    if (!$violationTypeId) {
                        $totalSkipped++;
                        continue;
                    }
                    
                    // Tìm hoặc tạo user
                    $user = $this->findOrCreateUser($bienSo);
                    
                    // Tìm hoặc tạo vehicle
                    $vehicleId = $this->findOrCreateVehicle($user['id'], $bienSo, $doiTuong, $mauBien);
                    
                    // Lấy mức phạt
                    $mucPhat = $this->getFineAmount($violationTypeId);
                    
                    // Tạo mã vi phạm
                    $maViPham = 'VP' . date('Y') . str_pad($totalImported + 1, 6, '0', STR_PAD_LEFT);
                    
                    // Insert violation
                    $stmt = $this->db->prepare("
                        INSERT INTO violations 
                        (ma_vi_pham, bien_so, vehicle_id, user_id, violation_type_id, 
                         thoi_gian_vi_pham, dia_diem, muc_phat, trang_thai, nguoi_lap_bien_ban) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Chưa xử lý', 1)
                    ");
                    
                    $diaDiem = 'Hà Nội'; // Có thể random địa điểm
                    
                    $stmt->execute([
                        $maViPham,
                        $bienSo,
                        $vehicleId,
                        $user['id'],
                        $violationTypeId,
                        $thoiGian,
                        $diaDiem,
                        $mucPhat
                    ]);
                    
                    $totalImported++;
                    
                } catch (Exception $e) {
                    echo " Lỗi: " . $e->getMessage() . "\n";
                    $totalSkipped++;
                }
            }
            
            echo "✅ Hoàn thành file: " . basename($jsonFile) . "\n\n";
        }
        
        echo "=== KẾT QUẢ IMPORT ===\n";
        echo " Đã import: $totalImported vi phạm\n";
        echo "  Bỏ qua: $totalSkipped bản ghi\n";
        echo "========================\n";
    }
}

// Chạy import
try {
    $importer = new ViolationImporter();
    $importer->import();
} catch (Exception $e) {
    echo " LỖI: " . $e->getMessage() . "\n";
}