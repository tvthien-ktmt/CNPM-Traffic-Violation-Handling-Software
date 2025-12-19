```markdown
# PHẦN MỀM XỬ PHẠT VI PHẠM GIAO THÔNG (WEB)

---

## 1. Giới thiệu

Đây là dự án **Web xử phạt vi phạm giao thông** tích hợp **AI nhận diện vi phạm** từ camera (YOLOv8) và **hệ thống quản lý – thanh toán – tra cứu vi phạm** cho người dân và cán bộ.

Hệ thống gồm 2 khối chính:

* **AI Service (Python + YOLOv8)**: Phát hiện vi phạm, nhận diện phương tiện, biển số, đèn giao thông, mũ bảo hiểm.
* **Web Backend (PHP)**: Quản lý vi phạm, người dùng, cán bộ, thanh toán, xuất biên bản.

---

## 2. Công nghệ sử dụng

* **AI**: YOLOv8 (Python)
* **Backend**: PHP (MVC custom)
* **Frontend**: HTML, CSS, JavaScript
* **Cơ sở dữ liệu**: MySQL / SQL
* **Thanh toán**: VNPay, MoMo, SePay (QR Code)
* **PDF**: TCPDF
* **Web server**: Apache (XAMPP)

---

## 3. Cấu trúc tổng quan dự án

```text
traffic/
├── ai_service/        # AI xử lý vi phạm (Python)
├── app/               # Backend PHP (MVC)
├── config/            # Cấu hình hệ thống
├── database/          # SQL, schema, migration
├── public/            # Frontend assets + entry point
├── scripts/           # Script xử lý dữ liệu, callback
├── tests/             # Test
├── .env               # Biến môi trường
└── README.md
```

---

## 4. Yêu cầu môi trường

### 4.1 Phần mềm bắt buộc

* Windows
* **XAMPP** (Apache + MySQL + PHP >= 8.0)
* **Python >= 3.9** (chạy AI service)
* Git

### 4.2 Extension PHP cần bật

Trong `php.ini`:

* pdo_mysql
* mysqli
* openssl
* curl
* gd

---

## 5. Cài đặt & cấu hình chạy dự án

### BƯỚC 1: Cài đặt XAMPP

* Tải XAMPP tại: [https://www.apachefriends.org](https://www.apachefriends.org)
* Cài đặt mặc định
* Thư mục web root:

```text
D:\xampp\htdocs\
```

---

### BƯỚC 2: Clone / copy dự án

Đặt toàn bộ source vào:

```text
D:\xampp\htdocs\traffic
```

Đường dẫn truy cập sau khi chạy:

```text
http://localhost/traffic/public
```

---

### BƯỚC 3: Cấu hình Virtual Host (khuyến nghị)

Mở file:

```text
D:\xampp\apache\conf\extra\httpd-vhosts.conf
```

Thêm:

```apache
<VirtualHost *:80>
    DocumentRoot "D:/xampp/htdocs/traffic/public"
    ServerName traffic.local
    <Directory "D:/xampp/htdocs/traffic/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Mở file `hosts`:

```text
C:\Windows\System32\drivers\etc\hosts
```

Thêm:

```text
127.0.0.1 traffic.local
```

Restart Apache.

---

### BƯỚC 4: Cấu hình database

#### 4.1 Tạo database

Mở phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Tạo database:

```sql
CREATE DATABASE traffic_violation CHARACTER SET utf8mb4;
```

Import file:

```text
database/schema.sql
database/create_database.sql
```

---

#### 4.2 Cấu hình kết nối DB

Mở file:

```text
config/database.php
```

Sửa:

```php
return [
    'host' => 'localhost',
    'dbname' => 'traffic_violation',
    'username' => 'root',
    'password' => '',
];
```

---

### BƯỚC 5: Cấu hình backend PHP

Mở:

```text
config/config.php
```

Kiểm tra:

```php
'base_url' => 'http://traffic.local',
```

Hoặc nếu không dùng virtual host:

```php
'base_url' => 'http://localhost/traffic/public',
```

---

### BƯỚC 6: Cấu hình AI Service (Python)

#### 6.1 Tạo môi trường ảo

```bash
cd D:\xampp\htdocs\traffic\ai_service
python -m venv venv
venv\Scripts\activate
```

#### 6.2 Cài thư viện

```bash
pip install -r requiment.txt
```

#### 6.3 Chạy AI service

```bash
start_ai_service.bat
```

Hoặc:

```bash
uvicorn app:app --reload
```

---

### BƯỚC 7: Phân quyền thư mục

Đảm bảo các thư mục có quyền ghi:

```text
crop/
full/
app/views/violations/data_xuphat/
```

---

## 6. Chức năng hệ thống

### 6.1 Trang người dân

* Trang chủ hiển thị tin tức
* Tra cứu vi phạm bằng **biển số xe**
* Chatbot tra cứu lỗi & mức phạt
* Thanh toán vi phạm bằng **QR Code** (VNPay / MoMo)
* Nhận callback xác nhận thanh toán
* Xuất **biên lai PDF**
* Tra cứu lịch sử vi phạm
* Tự động xóa lỗi sau khi thanh toán thành công

---

### 6.2 Trang cán bộ

* Đăng nhập (SĐT + mật khẩu)
* Xem camera, video vi phạm
* Lập biên bản tự động từ AI
* Lập biên bản thủ công
* In biên bản
* Xem danh sách vi phạm
* Xem & quản lý biên lai
* Thu tiền thủ công và xóa lỗi

---

## 7. Entry point hệ thống

```text
/public/index.php   # Front Controller
```

Routing:

```text
config/routes.php
```

---

## 8. Tài khoản mặc định (nếu có)

```text
Cán bộ:
SĐT: 0123456789
Mật khẩu: 123456
```

(Thay đổi trong database nếu cần)

---

## 9. Ghi chú quan trọng

* Phải chạy **AI Service trước**, sau đó mới chạy web
* Callback thanh toán cần public domain khi demo thật
* Không commit `.env`, `venv`, `vendor`

---

## 10. Tác giả

Sinh viên thực hiện đồ án **Hệ thống xử phạt vi phạm giao thông thông minh**: 
- Trương Vĩnh Thiện -- 22KTMT1
- Thân Công Đức - 22KTMT2
- Ngô Trung Chinh - 22KTMT1
- Lưu Văn Thành Huy - 22KTMT1
- Lê Thanh Bản - 22KTMT1
```
