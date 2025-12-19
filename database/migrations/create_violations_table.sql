CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_plate VARCHAR(20) NOT NULL,
    vehicle_type ENUM('car', 'motorcycle') NOT NULL,
    violation_type_id INT,
    violation_date DATETIME NOT NULL,
    location TEXT,
    fine_amount DECIMAL(12,2),
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    evidence_image VARCHAR(255),
    officer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (violation_type_id) REFERENCES violation_types(id)
);

CREATE TABLE violation_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    violation_code VARCHAR(50) NOT NULL,
    violation_name VARCHAR(255) NOT NULL,
    fine_amount DECIMAL(12,2) NOT NULL,
    legal_basis TEXT,
    vehicle_type ENUM('car', 'motorcycle') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);