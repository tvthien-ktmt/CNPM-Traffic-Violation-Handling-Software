-- Sử dụng database traffic_db
CREATE DATABASE IF NOT EXISTS traffic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE traffic_db;

-- Xóa bảng cũ nếu có
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS violation_deletion_history;
DROP TABLE IF EXISTS receipts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS violations;
DROP TABLE IF EXISTS officers;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS violation_types;
SET FOREIGN_KEY_CHECKS = 1;

-- Tạo bảng users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ho_ten VARCHAR(255) NOT NULL,
    cccd VARCHAR(12) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE,
    so_dien_thoai VARCHAR(15) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    dia_chi TEXT,
    ngay_sinh DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cccd (cccd),
    INDEX idx_so_dien_thoai (so_dien_thoai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng vehicles
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bien_so VARCHAR(20) UNIQUE NOT NULL,
    loai_xe ENUM('Xe mô tô', 'Ô tô con', 'Ô tô tải từ 3,5 tấn', 'Xe điện', 'Những loại khác') NOT NULL,
    mau_bien VARCHAR(50) DEFAULT 'Trắng',
    hang_xe VARCHAR(100),
    mau_xe VARCHAR(50),
    nam_san_xuat YEAR,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bien_so (bien_so),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng violation_types
CREATE TABLE violation_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_loi VARCHAR(20) UNIQUE NOT NULL,
    ten_loi VARCHAR(255) NOT NULL,
    muc_phat_toi_thieu DECIMAL(12,0) NOT NULL,
    muc_phat_toi_da DECIMAL(12,0) NOT NULL,
    diem_tru INT DEFAULT 0,
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert loại vi phạm
INSERT INTO violation_types (ma_loi, ten_loi, muc_phat_toi_thieu, muc_phat_toi_da, diem_tru, mo_ta) VALUES
('VD01', 'Vượt đèn đỏ', 4000000, 6000000, 2, 'Vi phạm không chấp hành hiệu lệnh đèn tín hiệu giao thông'),
('KDM01', 'Không đội mũ bảo hiểm', 400000, 600000, 0, 'Người điều khiển xe mô tô không đội mũ bảo hiểm'),
('KDD01', 'Không thắt dây đai an toàn', 800000, 1000000, 0, 'Người lái xe ô tô không thắt dây đai an toàn'),
('QT01', 'Vượt quá tốc độ cho phép', 2000000, 4000000, 2, 'Chạy quá tốc độ quy định từ 20-35 km/h'),
('SDDTD01', 'Sử dụng điện thoại khi lái xe', 800000, 1000000, 0, 'Sử dụng điện thoại di động khi đang điều khiển xe'),
('DXR01', 'Dừng đỗ xe sai quy định', 400000, 600000, 0, 'Dừng đỗ xe không đúng nơi quy định'),
('CNC01', 'Chạy ngược chiều', 4000000, 6000000, 3, 'Điều khiển xe chạy ngược chiều trên đường một chiều'),
('SDRU01', 'Nồng độ cồn vượt quá quy định', 6000000, 8000000, 4, 'Điều khiển xe mô tô có nồng độ cồn vượt quá mức cho phép'),
('KGL01', 'Không có giấy phép lái xe', 4000000, 6000000, 0, 'Điều khiển xe mà không có giấy phép lái xe'),
('CSL01', 'Chạy sai làn đường', 800000, 1000000, 1, 'Không đi đúng phần đường, làn đường quy định');

-- Tạo bảng violations
CREATE TABLE violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_vi_pham VARCHAR(50) UNIQUE NOT NULL,
    bien_so VARCHAR(20) NOT NULL,
    vehicle_id INT,
    user_id INT,
    violation_type_id INT NOT NULL,
    thoi_gian_vi_pham DATETIME NOT NULL,
    dia_diem TEXT,
    muc_phat DECIMAL(12,0) NOT NULL,
    trang_thai ENUM('Chưa xử lý', 'Đã thanh toán', 'Đã xóa') DEFAULT 'Chưa xử lý',
    hinh_anh VARCHAR(255),
    video VARCHAR(255),
    ghi_chu TEXT,
    nguoi_lap_bien_ban INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (violation_type_id) REFERENCES violation_types(id),
    INDEX idx_bien_so (bien_so),
    INDEX idx_trang_thai (trang_thai),
    INDEX idx_user_id (user_id),
    INDEX idx_thoi_gian (thoi_gian_vi_pham)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng payments
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_thanh_toan VARCHAR(50) UNIQUE NOT NULL,
    violation_id INT NOT NULL,
    user_id INT NOT NULL,
    so_tien DECIMAL(12,0) NOT NULL,
    phuong_thuc ENUM('SePay', 'VNPay', 'Momo', 'Thủ công') NOT NULL,
    trang_thai ENUM('Chờ thanh toán', 'Thành công', 'Thất bại', 'Đã hủy') DEFAULT 'Chờ thanh toán',
    sepay_transaction_id VARCHAR(100),
    sepay_reference_number VARCHAR(100),
    noi_dung_chuyen_khoan TEXT,
    thoi_gian_thanh_toan DATETIME,
    thoi_gian_xac_nhan DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ma_thanh_toan (ma_thanh_toan),
    INDEX idx_trang_thai (trang_thai),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng receipts
CREATE TABLE receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_bien_lai VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    violation_id INT NOT NULL,
    user_id INT NOT NULL,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tạo bảng officers
CREATE TABLE officers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ma_can_bo VARCHAR(20) UNIQUE NOT NULL,
    ho_ten VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(15) UNIQUE NOT NULL,
    mat_khau VARCHAR(255) NOT NULL,
    cap_bac VARCHAR(100),
    don_vi VARCHAR(255),
    email VARCHAR(255),
    trang_thai ENUM('Hoạt động', 'Tạm ngưng') DEFAULT 'Hoạt động',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert cán bộ mẫu
INSERT INTO officers (ma_can_bo, ho_ten, so_dien_thoai, mat_khau, cap_bac, don_vi, email) VALUES
('CB001', 'Phạm Văn Dũng', '0901234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thượng úy', 'Phòng CSGT Hà Nội', 'phamvandung@csgt.gov.vn');

-- Tạo bảng violation_deletion_history
CREATE TABLE violation_deletion_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    violation_id INT NOT NULL,
    officer_id INT,
    ly_do TEXT,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (officer_id) REFERENCES officers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Database created successfully!' as message;