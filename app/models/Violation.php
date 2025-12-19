<?php
/**
 * Violation Model
 * Chuẩn hoá biển số – JOIN đúng – Filter loại xe theo radio button
 */

require_once __DIR__ . '/../../config/database.php';

class Violation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Chuẩn hoá biển số: bỏ -, ., khoảng trắng, viết hoa
     */
    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $plate));
    }

    /**
     * Map giá trị radio button sang loại xe trong database
     */
    private function mapVehicleType(string $radioValue): array
    {
        switch($radioValue) {
            case '1': // Xe ô tô
                return ['Ô tô con', 'Ô tô tải từ 3,5 tấn'];
            case '2': // Xe máy
                return ['Xe mô tô'];
            case '3': // Xe điện
                return ['Xe điện'];
            case '4': // Loại khác
                return ['Những loại khác'];
            default:
                return [];
        }
    }

    /**
     * Lấy danh sách vi phạm theo biển số VÀ loại xe (THEO RADIO BUTTON)
     */
    public function getViolationsByLicensePlate(string $bienSo, ?string $vehicleType = null): array
    {
        try {
            $bienSo = $this->normalizePlate($bienSo);

            $sql = "
                SELECT 
                    v.*,
                    vt.ten_loi,
                    vt.ma_loi,
                    vt.muc_phat_toi_thieu,
                    vt.muc_phat_toi_da,
                    u.ho_ten,
                    u.cccd,
                    u.so_dien_thoai,
                    vh.loai_xe,
                    vh.mau_bien
                FROM violations v
                LEFT JOIN violation_types vt ON v.violation_type_id = vt.id
                LEFT JOIN users u ON v.user_id = u.id
                LEFT JOIN vehicles vh ON v.vehicle_id = vh.id
                WHERE 
                    REPLACE(REPLACE(REPLACE(UPPER(v.bien_so), '-', ''), '.', ''), ' ', '') = ?
            ";

            $params = [$bienSo];
            
            if ($vehicleType && $vehicleType !== 'all') {
                $allowedTypes = $this->mapVehicleType($vehicleType);
                
                if (!empty($allowedTypes)) {
                    $placeholders = str_repeat('?,', count($allowedTypes) - 1) . '?';
                    $sql .= " AND vh.loai_xe IN ($placeholders)";
                    $params = array_merge($params, $allowedTypes);
                } else {
                    return [];
                }
            }

            $sql .= " ORDER BY v.thoi_gian_vi_pham DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getViolationsByLicensePlate: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kiểm tra định dạng biển số theo loại xe
     */
    public function validateLicensePlateFormat(string $plate, string $vehicleType): bool
    {
        $plate = $this->normalizePlate($plate);
        
        if (empty($plate)) {
            return false;
        }
        
        $patterns = [
            '1' => '/^\d{2}[A-Z]{1,2}\d{4,6}$/',
            '2' => '/^\d{2}[A-Z]{1,2}\d{5,6}$/',
            '3' => '/^\d{2}[A-Z]{2}\d{4,5}$/',
            '4' => '/^.*$/'
        ];
        
        return isset($patterns[$vehicleType]) && preg_match($patterns[$vehicleType], $plate);
    }

    /**
     * Lấy chi tiết vi phạm theo ID
     */
    public function getViolationById(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    v.*,
                    vt.ten_loi,
                    vt.ma_loi,
                    vt.muc_phat_toi_thieu,
                    vt.muc_phat_toi_da,
                    u.ho_ten,
                    u.cccd,
                    u.email,
                    u.so_dien_thoai,
                    u.dia_chi,
                    vh.bien_so,
                    vh.loai_xe,
                    vh.hang_xe,
                    vh.mau_xe
                FROM violations v
                LEFT JOIN violation_types vt ON v.violation_type_id = vt.id
                LEFT JOIN users u ON v.user_id = u.id
                LEFT JOIN vehicles vh ON v.vehicle_id = vh.id
                WHERE v.id = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log('getViolationById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Đánh dấu vi phạm đã thanh toán
     */
    public function markAsPaid(int $violationId): bool
    {
        try {
            $sql = "
                UPDATE violations
                SET trang_thai = 'Đã thanh toán',
                    updated_at = NOW()
                WHERE id = ?
            ";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$violationId]);
        } catch (PDOException $e) {
            error_log('markAsPaid: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật trạng thái vi phạm
     */
    public function updateViolationStatus($violationId, $status): bool
    {
        try {
            $sql = "UPDATE violations SET trang_thai = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $violationId]);
        } catch (PDOException $e) {
            error_log('updateViolationStatus: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Thanh toán nhiều vi phạm cùng lúc
     */
    public function payMultipleViolations(array $violationIds): bool
    {
        try {
            $placeholders = str_repeat('?,', count($violationIds) - 1) . '?';
            
            $sql = "
                UPDATE violations
                SET trang_thai = 'Đã thanh toán',
                    updated_at = NOW()
                WHERE id IN ($placeholders)
                  AND trang_thai = 'Chưa xử lý'
            ";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($violationIds);
        } catch (PDOException $e) {
            error_log('payMultipleViolations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách vi phạm chưa thanh toán theo biển số
     */
    public function getUnpaidViolationsByPlate(string $bienSo): array
    {
        try {
            $bienSo = $this->normalizePlate($bienSo);
            
            $sql = "
                SELECT v.*
                FROM violations v
                WHERE REPLACE(REPLACE(REPLACE(UPPER(v.bien_so), '-', ''), '.', ''), ' ', '') = ?
                  AND v.trang_thai = 'Chưa xử lý'
                ORDER BY v.thoi_gian_vi_pham DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bienSo]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getUnpaidViolationsByPlate: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy vi phạm chưa xử lý của user
     */
    public function getUnpaidViolationsByUser(int $userId): array
    {
        try {
            $sql = "
                SELECT 
                    v.*,
                    vt.ten_loi,
                    vt.ma_loi,
                    vh.bien_so
                FROM violations v
                LEFT JOIN violation_types vt ON v.violation_type_id = vt.id
                LEFT JOIN vehicles vh ON v.vehicle_id = vh.id
                WHERE 
                    v.user_id = ?
                    AND v.trang_thai = 'Chưa xử lý'
                ORDER BY v.thoi_gian_vi_pham DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getUnpaidViolationsByUser: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Đếm số vi phạm theo trạng thái
     */
    public function countViolationsByStatus(string $status = 'Chưa xử lý'): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM violations WHERE trang_thai = ?"
            );
            $stmt->execute([$status]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('countViolationsByStatus: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Tổng tiền phạt chưa thanh toán
     */
    public function getTotalUnpaidAmount(int $userId): int
    {
        try {
            $sql = "
                SELECT COALESCE(SUM(muc_phat), 0)
                FROM violations
                WHERE user_id = ?
                  AND trang_thai = 'Chưa xử lý'
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('getTotalUnpaidAmount: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Tìm user theo biển số
     */
    public function getUserByLicensePlate(string $bienSo): ?array
    {
        try {
            $bienSo = $this->normalizePlate($bienSo);

            $sql = "
                SELECT u.*
                FROM users u
                INNER JOIN vehicles vh ON vh.user_id = u.id
                WHERE 
                    REPLACE(REPLACE(REPLACE(UPPER(vh.bien_so), '-', ''), '.', ''), ' ', '') = ?
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bienSo]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log('getUserByLicensePlate: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lấy vi phạm theo payment group ID
     */
    public function getViolationsByPaymentGroup(string $paymentGroupId): array
    {
        try {
            $sql = "
                SELECT v.*
                FROM violations v
                INNER JOIN payments p ON v.id = p.violation_id
                WHERE p.payment_group_id = ?
                ORDER BY v.id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$paymentGroupId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getViolationsByPaymentGroup: ' . $e->getMessage());
            return [];
        }
    }
}
?>