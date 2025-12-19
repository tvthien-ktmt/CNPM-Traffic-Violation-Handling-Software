<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/traffic/config/database.php';

class Officer {
    private $db;
    private $table = 'officers';
    
    public function __construct() {
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
    }
    
    // Đăng nhập cán bộ
    public function login($so_dien_thoai, $mat_khau) {
        $sql = "SELECT * FROM {$this->table} WHERE so_dien_thoai = :so_dien_thoai";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':so_dien_thoai' => $so_dien_thoai]);
        
        if ($stmt->rowCount() > 0) {
            $officer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($mat_khau, $officer['mat_khau'])) {
                return $officer;
            }
        }
        
        return false;
    }
    
    // Lấy thông tin cán bộ theo ID
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật thông tin cán bộ
    public function update($id, $data) {
        $fields = [];
        $values = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $values[":{$key}"] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($values);
    }
}
?>