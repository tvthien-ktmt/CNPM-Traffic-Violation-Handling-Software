<?php
session_start();

class ViolationController {
    private $db;

    public function __construct() {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/config/database.php';
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
    }

    // Tạo biên bản vi phạm
    public function createViolation() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lấy dữ liệu từ form
            $bien_so = $_POST['bien_so'] ?? '';
            $loai_xe = $_POST['loai_xe'] ?? '';
            $ho_ten_nguoi_vp = $_POST['ho_ten_nguoi_vp'] ?? '';
            $cccd = $_POST['cccd'] ?? '';
            $sdt_nguoi_vp = $_POST['sdt_nguoi_vp'] ?? '';
            $dia_chi = $_POST['dia_chi'] ?? '';
            $dia_diem = $_POST['dia_diem'] ?? '';
            $thoi_gian = $_POST['thoi_gian'] ?? '';
            $mo_ta = $_POST['mo_ta'] ?? '';
            $loi_vi_pham = $_POST['loi_vi_pham'] ?? [];
            
            // ID cán bộ xử lý
            $can_bo_id = $_SESSION['officer_id'] ?? 0;
            $ma_can_bo = $_SESSION['ma_can_bo'] ?? '';
            
            // Tạo mã biên bản
            $ma_bien_ban = 'BB' . date('YmdHis') . rand(100, 999);
            
            // Tính tổng tiền phạt
            $tong_tien = 0;
            foreach ($loi_vi_pham as $ma_loi) {
                $tong_tien += $this->getViolationPrice($ma_loi);
            }
            
            // Lưu vào database
            $sql = "INSERT INTO violations (ma_bien_ban, bien_so, loai_xe, ho_ten_nguoi_vp, cccd, sdt_nguoi_vp, 
                                           dia_chi, dia_diem, thoi_gian, mo_ta, tong_tien, can_bo_id, 
                                           ma_can_bo, trang_thai, created_at) 
                    VALUES (:ma_bien_ban, :bien_so, :loai_xe, :ho_ten_nguoi_vp, :cccd, :sdt_nguoi_vp, 
                            :dia_chi, :dia_diem, :thoi_gian, :mo_ta, :tong_tien, :can_bo_id, 
                            :ma_can_bo, 'pending', NOW())";
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':ma_bien_ban' => $ma_bien_ban,
                    ':bien_so' => $bien_so,
                    ':loai_xe' => $loai_xe,
                    ':ho_ten_nguoi_vp' => $ho_ten_nguoi_vp,
                    ':cccd' => $cccd,
                    ':sdt_nguoi_vp' => $sdt_nguoi_vp,
                    ':dia_chi' => $dia_chi,
                    ':dia_diem' => $dia_diem,
                    ':thoi_gian' => $thoi_gian,
                    ':mo_ta' => $mo_ta,
                    ':tong_tien' => $tong_tien,
                    ':can_bo_id' => $can_bo_id,
                    ':ma_can_bo' => $ma_can_bo
                ]);
                
                // Lưu chi tiết lỗi vi phạm
                foreach ($loi_vi_pham as $ma_loi) {
                    $this->saveViolationDetail($ma_bien_ban, $ma_loi);
                }
                
                // Chuyển hướng về trang tạo biên bản với thông báo thành công
                header('Location: /traffic/app/views/officers/add_violation.php?success=1');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['error'] = "Lỗi khi lưu biên bản: " . $e->getMessage();
                header('Location: /traffic/app/views/officers/add_violation.php');
                exit();
            }
        }
    }
    
    private function getViolationPrice($ma_loi) {
        $prices = [
            'VP001' => 800000,
            'VP002' => 200000,
            'VP003' => 1500000,
            'VP004' => 300000,
            'VP005' => 1000000,
            'VP006' => 500000
        ];
        return $prices[$ma_loi] ?? 0;
    }
    
    private function saveViolationDetail($ma_bien_ban, $ma_loi) {
        try {
            $sql = "INSERT INTO violation_details (ma_bien_ban, ma_loi) VALUES (:ma_bien_ban, :ma_loi)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':ma_bien_ban' => $ma_bien_ban,
                ':ma_loi' => $ma_loi
            ]);
        } catch (PDOException $e) {
            // Có thể log lỗi nhưng không làm break quá trình
            error_log("Lỗi khi lưu chi tiết vi phạm: " . $e->getMessage());
        }
    }
    
    // Lấy danh sách vi phạm
    public function getViolations($limit = 10) {
        $can_bo_id = $_SESSION['officer_id'] ?? 0;
        
        $sql = "SELECT v.*, 
                       GROUP_CONCAT(vd.ma_loi) as danh_sach_loi,
                       COUNT(vd.id) as so_loi
                FROM violations v
                LEFT JOIN violation_details vd ON v.ma_bien_ban = vd.ma_bien_ban
                WHERE v.can_bo_id = :can_bo_id
                GROUP BY v.id
                ORDER BY v.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':can_bo_id', $can_bo_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

// Router đơn giản
if (isset($_GET['action'])) {
    $violation = new ViolationController();
    
    switch ($_GET['action']) {
        case 'create':
            $violation->createViolation();
            break;
        case 'list':
            $violations = $violation->getViolations();
            // Có thể trả về JSON hoặc include view
            break;
        default:
            // Không làm gì
            break;
    }
}
?>