<?php
class Payment {
    private $pdo;
    private $tableColumns = null;
    
    public function __construct() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->loadTableColumns();
            
            // Debug: Log column info
            error_log("[Payment Model] Columns loaded: " . implode(', ', array_keys($this->tableColumns)));
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Tải thông tin cột
     */
    private function loadTableColumns() {
        if ($this->tableColumns === null) {
            $this->tableColumns = [];
            try {
                $sql = "DESCRIBE payments";
                $stmt = $this->pdo->query($sql);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($columns as $column) {
                    $this->tableColumns[$column['Field']] = true;
                }
            } catch (Exception $e) {
                error_log('Load table columns error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Kiểm tra cột tồn tại
     */
    private function columnExists($columnName) {
        return isset($this->tableColumns[$columnName]);
    }
    
    /**
     * Tạo payment record mới - FIXED VERSION
     */
    public function createPayment($data) {
        try {
            $columns = [];
            $placeholders = [];
            $values = [];
            
            // Debug log
            error_log("[Payment Model] createPayment called with data keys: " . implode(', ', array_keys($data)));
            
            // DANH SÁCH TẤT CẢ CÁC TRƯỜNG CÓ THỂ CÓ
            $allPossibleFields = [
                'ma_thanh_toan',
                'violation_id', 
                'user_id',
                'so_tien',
                'so_tien_hien_thi',  // QUAN TRỌNG
                'phep_chia',         // QUAN TRỌNG
                'phuong_thuc',
                'trang_thai',
                'noi_dung_chuyen_khoan',
                'payment_group_id',
                'created_at',
                'sepay_transaction_id',
                'reference_number',
                'bank_account_id',
                'thoi_gian_thanh_toan',
                'thoi_gian_xac_nhan',
                'updated_at',
                'transaction_id',
                'bank_brand_name',
                'transaction_content'
            ];
            
            // Thêm các trường có trong dữ liệu VÀ tồn tại trong bảng
            foreach ($allPossibleFields as $field) {
                if (isset($data[$field]) && $this->columnExists($field)) {
                    $columns[] = $field;
                    $placeholders[] = '?';
                    $values[] = $data[$field];
                    
                    // Debug các trường quan trọng
                    if (in_array($field, ['so_tien_hien_thi', 'phep_chia'])) {
                        error_log("[Payment Model] Adding column $field = " . $data[$field]);
                    }
                }
            }
            
            // Debug: Log columns being inserted
            error_log("[Payment Model] Inserting columns: " . implode(', ', $columns));
            error_log("[Payment Model] Values count: " . count($values));
            
            if (empty($columns)) {
                throw new Exception('Không có cột nào để chèn dữ liệu');
            }
            
            $sql = "INSERT INTO payments (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            // Debug SQL
            error_log("[Payment Model] SQL: $sql");
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($values);
            
            if ($success) {
                $lastId = $this->pdo->lastInsertId();
                error_log("[Payment Model] Payment created successfully with ID: $lastId");
                return $lastId;
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("[Payment Model] SQL Error: " . ($errorInfo[2] ?? 'Unknown'));
                return false;
            }
            
        } catch (Exception $e) {
            error_log('Create payment error: ' . $e->getMessage());
            error_log('Create payment trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Lấy payment theo reference
     */
    public function getPaymentByReference($reference) {
        try {
            $sql = "SELECT * FROM payments 
                    WHERE noi_dung_chuyen_khoan = :reference 
                    OR reference_number = :reference 
                    OR sepay_reference_number = :reference
                    ORDER BY id DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':reference' => $reference]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Get payment by reference error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy payment theo content
     */
    public function getPaymentByContent($paymentCode) {
        try {
            $sql = "SELECT * FROM payments 
                    WHERE noi_dung_chuyen_khoan = :payment_code 
                    ORDER BY id DESC LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_code' => $paymentCode]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Get payment by content error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy violation IDs theo group
     */
    public function getViolationIdsByGroupId($groupId) {
        try {
            $sql = "SELECT violation_id FROM payments 
                    WHERE payment_group_id = :group_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':group_id' => $groupId]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_column($results, 'violation_id');
            
        } catch (Exception $e) {
            error_log('Get violation IDs error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update payment by group ID
     */
    public function updatePaymentByGroupId($groupId, $data) {
        try {
            $sql = "UPDATE payments SET ";
            $params = [];
            $updates = [];
            
            foreach ($data as $key => $value) {
                if ($this->columnExists($key)) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql .= implode(', ', $updates);
            $sql .= " WHERE payment_group_id = ?";
            $params[] = $groupId;
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log('Update payment by group error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payments by group ID
     */
    public function getPaymentsByGroupId($groupId) {
        try {
            $sql = "SELECT * FROM payments WHERE payment_group_id = ? ORDER BY id ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Get payments by group error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lấy thông tin payment group - FIXED VERSION
     */
    public function getPaymentGroupInfo($groupId) {
        try {
            // Xác định các cột có thể SELECT
            $selectFields = [
                "payment_group_id",
                "COUNT(*) as payment_count",
                "SUM(so_tien) as total_amount_original",
                "GROUP_CONCAT(violation_id) as violation_ids",
                "MIN(trang_thai) as trang_thai"
            ];
            
            // Thêm các trường có điều kiện
            $conditionalFields = [
                'thoi_gian_xac_nhan' => "MAX(thoi_gian_xac_nhan) as thoi_gian_xac_nhan",
                'sepay_transaction_id' => "MAX(sepay_transaction_id) as sepay_transaction_id",
                'reference_number' => "MAX(reference_number) as reference_number",
                'so_tien_hien_thi' => "SUM(so_tien_hien_thi) as total_amount_display",
                'phep_chia' => "MAX(phep_chia) as divide_rule"
            ];
            
            foreach ($conditionalFields as $column => $selectClause) {
                if ($this->columnExists($column)) {
                    $selectFields[] = $selectClause;
                }
            }
            
            // Fallback nếu không có so_tien_hien_thi
            if (!$this->columnExists('so_tien_hien_thi')) {
                $selectFields[] = "SUM(so_tien) as total_amount_display";
            }
            
            // Fallback nếu không có phep_chia
            if (!$this->columnExists('phep_chia')) {
                $selectFields[] = "1 as divide_rule";
            }
            
            $sql = "SELECT " . implode(', ', $selectFields) . "
                    FROM payments 
                    WHERE payment_group_id = :group_id 
                    GROUP BY payment_group_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':group_id' => $groupId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if (!empty($result['violation_ids'])) {
                    $result['violation_ids_array'] = explode(',', $result['violation_ids']);
                } else {
                    $result['violation_ids_array'] = [];
                }
                
                // Đảm bảo giá trị mặc định
                $result['total_amount_display'] = $result['total_amount_display'] ?? $result['total_amount_original'];
                $result['divide_rule'] = $result['divide_rule'] ?? 1;
                $result['trang_thai'] = $result['trang_thai'] ?? 'Chờ thanh toán';
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Get payment group info error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total amount by group ID
     */
    public function getTotalAmountByGroupId($groupId) {
        try {
            $sql = "SELECT SUM(so_tien) as total_amount FROM payments WHERE payment_group_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$groupId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? floatval($result['total_amount']) : 0;
            
        } catch (Exception $e) {
            error_log('Get total amount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Lấy trạng thái thanh toán
     */
    public function getPaymentStatusByGroupId($groupId) {
        try {
            $sql = "SELECT trang_thai FROM payments 
                    WHERE payment_group_id = :group_id 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':group_id' => $groupId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['trang_thai'] ?? 'Chờ thanh toán';
            
        } catch (Exception $e) {
            error_log('Get payment status error: ' . $e->getMessage());
            return 'Chờ thanh toán';
        }
    }
    
    /**
     * Update single payment
     */
    public function updatePayment($paymentId, $data) {
        try {
            $sql = "UPDATE payments SET ";
            $params = [];
            $updates = [];
            
            foreach ($data as $key => $value) {
                if ($this->columnExists($key)) {
                    $updates[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $sql .= implode(', ', $updates);
            $sql .= " WHERE id = :id";
            $params[':id'] = $paymentId;
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log('Update payment error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kiểm tra payment tồn tại
     */
    public function paymentExists($paymentCode, $groupId = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM payments WHERE noi_dung_chuyen_khoan = ?";
            $params = [$paymentCode];
            
            if ($groupId) {
                $sql .= " AND payment_group_id = ?";
                $params[] = $groupId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log('Payment exists check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * PUBLIC: Kiểm tra cột tồn tại
     */
    public function checkColumnExists($columnName) {
        return $this->columnExists($columnName);
    }
    
    /**
     * PUBLIC: Lấy tất cả cột
     */
    public function getAllColumns() {
        return array_keys($this->tableColumns);
    }
    
    /**
     * Lấy lỗi cuối cùng
     */
    public function getLastError() {
        if ($this->pdo) {
            $errorInfo = $this->pdo->errorInfo();
            return isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown error';
        }
        return 'PDO not initialized';
    }
}
?>