<?php
// File: /traffic/app/models/User.php

class User {
    private $db;
    
    public function __construct() {
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=traffic_db;charset=utf8mb4', 'root', '');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }
    
    /**
     * Kiểm tra đăng nhập - ĐÃ FIX HOÀN TOÀN
     */
    public function checkLogin($phone, $password) {
        try {
            // CHỈNH SỬA QUAN TRỌNG: Kiểm tra cả 'Hoạt động' và '1'
            $sql = "SELECT 
                        id,
                        ma_can_bo,
                        ho_ten as full_name,
                        so_dien_thoai as phone,
                        mat_khau_hien_thi,
                        cap_bac as rank,
                        don_vi as unit,
                        email,
                        trang_thai as status
                    FROM officers 
                    WHERE so_dien_thoai = ? 
                    AND (trang_thai = 'Hoạt động' OR trang_thai = '1' OR trang_thai IS NULL)
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$phone]);
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$officer) {
                return ['success' => false, 'error' => 'Số điện thoại không tồn tại hoặc tài khoản bị khóa'];
            }
            
            // Kiểm tra mật khẩu
            if ($password === $officer['mat_khau_hien_thi']) {
                // Tự động fix status nếu cần
                if ($officer['status'] === '1' || $officer['status'] === '' || $officer['status'] === NULL) {
                    $this->autoFixStatus($officer['id']);
                    $officer['status'] = 'Hoạt động';
                }
                
                return ['success' => true, 'officer' => $officer];
            }
            
            return ['success' => false, 'error' => 'Mật khẩu không đúng'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }
    
    /**
     * Tự động update status về 'Hoạt động' nếu là '1' hoặc empty
     */
    public function autoFixStatus($officerId) {
        try {
            $sql = "UPDATE officers 
                    SET trang_thai = 'Hoạt động', 
                        updated_at = NOW() 
                    WHERE id = ? AND (trang_thai = '1' OR trang_thai = '' OR trang_thai IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$officerId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("AutoFixStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy thông tin officer theo ID
     */
    public function getOfficerById($id) {
        try {
            $sql = "SELECT * FROM officers WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
}