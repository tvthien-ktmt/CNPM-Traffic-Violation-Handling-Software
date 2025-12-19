<?php
/**
 * Database Configuration and Connection
 * File: config/database.php
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Cấu hình database
    private $host = 'localhost';
    private string $dbname = 'traffic_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    // Private constructor để implement Singleton
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }
    
    /**
     * Lấy instance duy nhất của Database (Singleton Pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Lấy PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Phương thức connect để tương thích với code cũ (THÊM VÀO)
     */
    public function connect() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialize
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>