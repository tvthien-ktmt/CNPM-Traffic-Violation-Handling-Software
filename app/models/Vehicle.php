<?php
class Vehicle {
    private $conn;
    private $table_name = "vehicles";

    public $license_plate;
    public $vehicle_type;
    public $owner_name;
    public $registration_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getVehicleInfo($license_plate) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE license_plate = :license_plate";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":license_plate", $license_plate);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>