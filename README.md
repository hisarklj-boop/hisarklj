# 🏫 School Canteen E-Commerce System

A comprehensive, modern school canteen management system built with PHP, MySQL, and modern UI components. Features separate student and admin interfaces with advanced security measures and mobile-optimized design.

## 🌟 Features Overview

### 👨‍🎓 Student Portal
**Mobile-First Login System**: 10-digit phone authentication starting with '5' **6-Digit Numeric Password**: Enhanced security with numeric-only passwords **Class-Based Product Display**: Students see products available for their grade level **Smart Shopping Cart**: Add, remove, update quantities with real-time calculations **Secure Checkout Process**: Multi-step payment with KVKK compliance **Payment Options**: Bank Transfer (EFT) and Credit Card processing **Order Tracking**: Complete order history and status updates **Remember Me**: 30-day secure cookie authentication **Password Recovery**: OTP-based password reset (test code: 111112) **Mobile Keyboard Support**: Numeric input mode for phones and tablets

### 👨‍💼 Admin Dashboard
**Comprehensive Statistics**: Real-time metrics and analytics **Product Management**: Full CRUD operations with image upload **Student Management**: Complete student database with class assignments **Order Management**: Process and update order statuses **Revenue Reports**: Detailed financial tracking and reporting **Inventory Control**: Stock management with low-stock alerts **Class-Based Product Assignment**: Assign products to specific grade levels **Secure Admin Authentication**: Same security model as student portal

### 🔐 Security Features
**Phone Number Validation**: Strict 10-digit format starting with '5' **Numeric Password System**: 6-digit numeric passwords only **Mobile Input Optimization**: Automatic numeric keyboard on mobile devices **Password Hashing**: Secure bcrypt password storage **SQL Injection Protection**: Prepared statements throughout **CSRF Protection**: Form token validation **Session Management**: Secure session handling with timeout **Cookie Security**: HTTPOnly and Secure cookie flags **OTP System**: Time-limited one-time passwords (60-second expiry) **Role-Based Access Control**: Separate student and admin authentication

## 🏗️ Technical Architecture

### 📁 Directory Structure
gebzehisarstore/ ├── admin/ │ ├── dashboard.php │ ├── login.php │ ├── logout.php │ ├── forgot-password.php │ ├── products.php │ ├── orders.php │ └── students.php ├── student/ │ ├── dashboard.php │ ├── login.php │ ├── logout.php │ ├── forgot-password.php │ ├── cart.php │ ├── checkout.php │ ├── credit-card.php │ ├── order-success.php │ ├── orders.php │ └── profile.php ├── config/ │ └── database.php ├── uploads/ │ ├── products/ │ └── profiles/ ├── assets/ │ ├── css/ │ ├── js/ │ └── images/ ├── database/ │ ├── schema.sql │ └── seed.sql ├── index.php └── README.md

### 🗄️ Database Schema

#### Users Table
CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, phone VARCHAR(10) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, role ENUM('admin', 'student') NOT NULL, remember_token VARCHAR(64) NULL, reset_otp VARCHAR(6) NULL, otp_expiry TIMESTAMP NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);

#### Students Table
CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, surname VARCHAR(100) NOT NULL, student_number VARCHAR(20) UNIQUE NOT NULL, class VARCHAR(50) NOT NULL, address TEXT NULL, photo VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);

#### Products Table
CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, code VARCHAR(50) UNIQUE NOT NULL, price DECIMAL(10,2) NOT NULL, stock INT DEFAULT 0, allowed_classes JSON NULL, is_active BOOLEAN DEFAULT TRUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);

#### Orders Table
CREATE TABLE orders (id INT AUTO_INCREMENT PRIMARY KEY, order_number VARCHAR(50) UNIQUE NOT NULL, student_id INT NOT NULL, total_amount DECIMAL(10,2) NOT NULL, payment_method ENUM('eft', 'card') NOT NULL, status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE);

#### Order Items Table
CREATE TABLE order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price DECIMAL(10,2) NOT NULL, subtotal DECIMAL(10,2) NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE);

#### Product Images Table
CREATE TABLE product_images (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, image_path VARCHAR(255) NOT NULL, is_primary BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE);

## ⚙️ Server Configuration

### 🌐 Hosting Environment
**Domain**: gebzehisarstore.com **Hosting Provider**: Namecheap Shared Hosting **Plan**: Stellar Plus **SSL Certificate**: Let's Encrypt (Auto-renewal enabled) **CDN**: Cloudflare integration ready

### 🔧 Server Specifications
**PHP Version**: 8.1+ (Recommended: 8.2) **MySQL Version**: 8.0+ **Web Server**: Apache 2.4+ with mod_rewrite **Memory Limit**: 512MB (minimum 256MB) **Upload Limit**: 64MB (for product images) **Execution Time**: 300 seconds

### 📂 Server Paths
**Document Root**: /public_html/ **Application Root**: /public_html/gebzehisarstore/ **Upload Directory**: /public_html/gebzehisarstore/uploads/ **Config Directory**: /public_html/gebzehisarstore/config/ **Log Directory**: /public_html/logs/

### 🗄️ Database Configuration
<?php session_start(); $host = 'localhost'; $dbname = 'okul_kantin'; $username = 'your_db_username'; $password = 'your_db_password'; $charset = 'utf8mb4'; $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset"; $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false,]; try { $db = new PDO($dsn, $username, $password, $options); } catch (PDOException $e) { throw new PDOException($e->getMessage(), (int)$e->getCode()); } ?>

## 🚀 Installation Guide

### 1️⃣ Server Preparation
php -m | grep -E "(pdo|mysql|gd|mbstring|openssl)" chmod 755 /public_html/gebzehisarstore/ chmod 777 /public_html/gebzehisarstore/uploads/ chmod 644 /public_html/gebzehisarstore/config/database.php

### 2️⃣ Database Setup
CREATE DATABASE okul_kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'canteen_user'@'localhost' IDENTIFIED BY 'secure_password_here'; GRANT ALL PRIVILEGES ON okul_kantin.* TO 'canteen_user'@'localhost'; FLUSH PRIVILEGES; SOURCE database/schema.sql; SOURCE database/seed.sql;

### 3️⃣ Configuration
cp config/database.example.php config/database.php chmod 755 uploads/ chmod 755 uploads/products/ chmod 755 uploads/profiles/

### 4️⃣ Default Accounts
INSERT INTO users (phone, password, role) VALUES ('5551112233', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); INSERT INTO users (phone, password, role) VALUES ('5559876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'); INSERT INTO students (user_id, name, surname, class, student_number) VALUES ((SELECT id FROM users WHERE phone = '5559876543'), 'Mehmet', 'Öğrenci', '1. Sınıf', '12345');

**Default Login Credentials**: Admin: Phone: 5551112233, Password: 123456 Student: Phone: 5559876543, Password: 123456

## 🎨 Frontend Technologies

### 🎯 UI Framework
**Tailwind CSS**: Utility-first CSS framework **DaisyUI**: Component library for Tailwind CSS **Plus Jakarta Sans**: Google Fonts typography **Responsive Design**: Mobile-first approach

### 📱 Mobile Optimization
**Viewport Meta Tag**: Proper mobile scaling **Touch-Friendly Buttons**: Minimum 44px touch targets **Numeric Keyboards**: inputmode="numeric" for phone inputs **Gesture Support**: Swipe navigation where applicable

### 🖼️ Visual Elements
**Gradient Backgrounds**: CSS gradients for visual appeal **Card-Based Layout**: Clean, modern card designs **Icon System**: Heroicons SVG icon library **Loading States**: Spinner and skeleton screens **Toast Notifications**: Success/error message system

## 🔒 Security Implementation

### 🛡️ Authentication Security
if (!preg_match('/^5[0-9]{9}$/', $phone)) { throw new Exception('Invalid phone format'); } if (!preg_match('/^[0-9]{6}$/', $password)) { throw new Exception('Password must be 6 digits'); } $hashed_password = password_hash($password, PASSWORD_DEFAULT); if (password_verify($password, $stored_hash)) { /* Authentication successful */ }

### 🍪 Session & Cookie Management
ini_set('session.cookie_httponly', 1); ini_set('session.cookie_secure', 1); ini_set('session.use_strict_mode', 1); $remember_token = bin2hex(random_bytes(32)); setcookie('remember_user', $remember_token, ['expires' => time() + (30 * 24 * 60 * 60), 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);

### 🔐 OTP System
$otp = '111112'; $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute')); $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND reset_otp = ? AND otp_expiry > NOW()"); $stmt->execute([$phone, $otp]);

## 💳 Payment Integration

### 🏦 Bank Transfer (EFT)
**Manual Processing**: Admin verification required **Bank Details Display**: IBAN and reference number **Order Tracking**: Pending status until payment confirmation

### 💰 Credit Card Processing
**Secure Form**: Separate payment page **Card Validation**: Client-side format checking **Test Mode**: Sandbox environment ready

### 🧾 Order Management
**Order Numbers**: Unique format: ORD{YYYYMMDD}{RANDOM} **Status Tracking**: Multi-stage order progression **Email Notifications**: Order confirmation and updates **Invoice Generation**: PDF invoice creation

## 📊 Analytics & Reporting

### 📈 Dashboard Metrics
**Total Students**: Active student count **Product Inventory**: Stock levels and alerts **Order Statistics**: Daily, weekly, monthly reports **Revenue Tracking**: Income analytics with charts

### 📋 Report Generation
**Sales Reports**: Product performance analysis **Student Activity**: Purchase patterns and preferences **Inventory Reports**: Stock movement and reorder alerts **Financial Reports**: Revenue, taxes, and profit margins

## 🌐 API Endpoints

### 🔗 Internal API Structure
POST /student/cart.php - action: add|remove|update - product_id: integer - quantity: integer POST /student/checkout.php - payment_method: eft|card - kvkk_accept: boolean - terms_accept: boolean POST /admin/products.php - action: create|update|delete - product_data: array

## 🔧 Maintenance & Monitoring

### 📝 Logging System
error_log("Authentication failed for phone: " . $phone, 3, "/var/log/canteen.log"); $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)"); $stmt->execute([$user_id, 'login', json_encode(['ip' => $_SERVER['REMOTE_ADDR']])]);

### 🔄 Backup Strategy
**Database Backups**: Daily automated MySQL dumps **File Backups**: Weekly full file system backup **Version Control**: Git-based code versioning **Recovery Testing**: Monthly restore procedures

### 📊 Performance Monitoring
**Query Optimization**: Indexed database queries **Image Optimization**: Compressed product images **Cache Strategy**: PHP OpCache and browser caching **CDN Integration**: Static asset delivery optimization

## 🚦 Testing Procedures

### 🧪 Test Accounts
Admin Test Account: Phone: 5551112233, Password: 123456, Access: Full admin privileges Student Test Account: Phone: 5559876543, Password: 123456, Class: 1. Sınıf, Access: Student portal only OTP Test Code: 111112

### ✅ Feature Testing Checklist
Student login/logout functionality Admin login/logout functionality Password recovery with OTP Product browsing by class Shopping cart operations Checkout process completion Order status updates Mobile responsiveness Security validations Payment processing

## 🆘 Troubleshooting

### 🐛 Common Issues
**Database Connection Errors** Check database credentials in config/database.php - Verify MySQL service is running - Check user permissions **File Upload Issues** chmod 755 uploads/ - php -i | grep upload **Session Problems** session_start(); session_destroy(); // Check session configuration **Login Failures** SELECT * FROM users WHERE phone = '5559876543'; // Verify password hash - Check role assignment

## 📞 Support Information

### 🛠️ Development Team
**Lead Developer**: Full-stack PHP development **UI/UX Designer**: Modern, accessible interface design **Database Administrator**: MySQL optimization and security **DevOps Engineer**: Server configuration and deployment

### 📧 Contact Information
**Technical Support**: Available for configuration assistance **Bug Reports**: GitHub Issues preferred **Feature Requests**: Community-driven development **Security Issues**: Immediate response protocol

### 📚 Additional Resources
**PHP Documentation**: https://www.php.net/docs.php **MySQL Reference**: https://dev.mysql.com/doc/ **Tailwind CSS**: https://tailwindcss.com/docs **DaisyUI Components**: https://daisyui.com/components/

**© 2024 School Canteen E-Commerce System. All rights reserved.** **License**: MIT License - See LICENSE file for details **Version**: 1.0.0 **Last Updated**: December 2024
