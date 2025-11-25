<?php
class Violation {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getViolationsByLicensePlate($licensePlate) {
        $stmt = $this->db->prepare("
            SELECT v.*, vt.violation_name, vt.fine_amount 
            FROM violations v 
            LEFT JOIN violation_types vt ON v.violation_type_id = vt.id 
            WHERE v.license_plate = ? AND v.status != 'deleted'
            ORDER BY v.violation_date DESC
        ");
        $stmt->execute([$licensePlate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getViolationById($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, vt.violation_name, vt.fine_amount, vt.legal_basis
            FROM violations v 
            LEFT JOIN violation_types vt ON v.violation_type_id = vt.id 
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateViolationStatus($id, $status) {
        $stmt = $this->db->prepare("
            UPDATE violations SET status = ?, updated_at = NOW() WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }
}