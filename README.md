# 🎓 SCHOOL CANTEEN E-COMMERCE SYSTEM - COMPLETE DOCUMENTATION

## 🖥️ SERVER INFORMATION
IP: 92.205.110.57
Domain: gebzehisarstore.com
Hostname: 57.110.205.92.host.secureserver.net
OS: Ubuntu 22.04.5 LTS (GNU/Linux 5.15.0-144-generic x86_64)
SSH User: gebzehisarst (sudo privileges with NOPASSWD)
SSH Port: 22
Provider: GoDaddy VPS
Disk: 39GB total, 3.2GB used (9%)
RAM: 1.9GB total

## 🔐 CREDENTIALS & ACCESS
MySQL Root Password: Eticaret2025!
MySQL Web User: webadmin
MySQL Web Password: Web@Admin2025!
Database Name: okul_kantin
Admin Panel Login: 5551234567 / 123456
Student Login: 5559876543 / 123456
Admin URL: https://gebzehisarstore.com/admin/login.php
Student URL: https://gebzehisarstore.com/login.php
GitHub: https://github.com/hisarklj-boop/hisarklj

## 📦 INSTALLED SOFTWARE
Apache: 2.4.52 (Ports 80, 443)
PHP: 8.1.2-1ubuntu2.22
MySQL: 8.0.43-0ubuntu0.22.04.2
SSL: GoDaddy Certificate (Expires: Aug 31, 2026)
Certificate Path: /etc/ssl/godaddy/
Modules: ssl, headers, rewrite, mod_security2, mod_evasive
DocumentRoot: /var/www/html/gebzehisarstore

## 🔒 SECURITY CONFIGURATION
UFW Firewall: Active (22/tcp, 80/tcp, 443/tcp)
Fail2Ban: Active (SSH protection, maxretry=3, bantime=3600)
ModSecurity: Enabled (Web Application Firewall)
ModEvasive: Enabled (DDoS Protection)
HTTPS: Forced redirect from HTTP
SSL Configuration: A+ rating with HSTS
PHP Security: expose_php=Off, display_errors=Off
MySQL: Localhost only binding
Session Timeout: 30 minutes
Login Attempts: Max 5 per 15 minutes

## 💾 DATABASE SCHEMA (okul_kantin)
TABLES:
- users (id, phone, password, role, is_active, login_attempts, last_attempt, created_at)
- students (id, user_id, name, surname, class, address, photo, created_at)
- parents (id, student_id, name, surname, phone, address, created_at)
- products (id, title, code, description, price, stock, is_active, created_at)
- product_images (id, product_id, image_path, is_primary, sort_order)
- orders (id, student_id, total, status, payment_method, notes, created_at)
- order_items (id, order_id, product_id, quantity, price)
- settings (id, setting_key, setting_value, created_at)

## 📂 PROJECT STRUCTURE
/var/www/html/gebzehisarstore/
├── admin/
│   ├── dashboard.php (Statistics, recent orders display)
│   ├── login.php (Admin authentication page)
│   ├── profile.php (Admin profile & password management)
│   ├── products.php (Product CRUD - PENDING)
│   ├── students.php (Student management - PENDING)
│   ├── orders.php (Order management - PENDING)
│   └── settings.php (System settings - PENDING)
├── student/
│   ├── dashboard.php (Student homepage)
│   ├── profile.php (Profile, password change, photo upload)
│   └── orders.php (Order history - PENDING)
├── config/
│   └── database.php (PDO connection configuration)
├── uploads/ (chmod 777)
│   ├── products/ (Product images)
│   └── profiles/ (Profile photos)
├── assets/
│   ├── css/ (Currently using CDN)
│   ├── js/ (Currently using CDN)
│   └── images/
├── api/ (API endpoints - PENDING)
├── includes/ (PHP includes - PENDING)
├── login.php (Main student login)
├── logout.php (Session destroyer)
├── .htaccess (Apache configuration)
├── .gitignore (Git ignore rules)
└── test_password.php (Password hash generator)

## 🚀 IMPLEMENTATION STATUS
COMPLETED:
✅ Server setup with complete security
✅ Database schema with all tables
✅ Authentication system (phone + 6-digit PIN)
✅ Admin/Student login separation
✅ Admin dashboard with statistics
✅ Admin profile management
✅ Student profile with photo upload
✅ Password change functionality
✅ Session management with timeout
✅ Responsive design with DaisyUI/TailwindCSS
✅ GitHub repository integration
✅ SSL certificate configuration
✅ Security headers and firewall

PENDING:
⏳ Product management (CRUD operations)
⏳ Product image gallery (max 5 images)
⏳ Shopping cart system
⏳ Order processing workflow
⏳ Payment integration (Credit card, EFT)
⏳ Student registration by admin
⏳ Parent information management
⏳ Order status updates (pending/shipped/cancelled)
⏳ SMS notifications (NetGSM integration)
⏳ Settings page functionality
⏳ Search and filter features
⏳ Order history for students
⏳ Stock management
⏳ Report generation

## 🎨 DESIGN SPECIFICATIONS
Framework: DaisyUI + TailwindCSS (CDN)
Font: Plus Jakarta Sans (Google Fonts)
Primary Color: Dark Navy (#1e293b)
Secondary: White (#ffffff)
Accent: Blue (#3b82f6)
Layout: Responsive (mobile-first)
Admin Theme: Dark sidebar with white content area
Student Theme: Light with navy accents
Card Shadows: box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1)

## 🛠️ TECHNICAL DETAILS
PHP Version: 8.1.2 with extensions (curl, gd, mbstring, mysqli, pdo_mysql, xml, zip)
Database: MySQL 8.0 with InnoDB engine
Password Hashing: bcrypt (PASSWORD_DEFAULT)
Session Management: PHP native sessions
Form Protection: CSRF tokens required
Input Validation: Phone (10 digits), Password (6 digits)
File Upload: Max 5MB (jpg, jpeg, png, gif)
Character Encoding: UTF-8 (utf8mb4_unicode_ci)

## 📝 APACHE CONFIGURATION
Virtual Host: /etc/apache2/sites-available/gebzehisarstore-ssl.conf
HTTP Redirect: /etc/apache2/sites-available/000-default.conf
.htaccess: DirectoryIndex login.php, Options -Indexes
Enabled Modules: ssl, headers, rewrite, security2, evasive
Error Log: /var/log/apache2/ssl-error.log
Access Log: /var/log/apache2/ssl-access.log

## 🆘 QUICK COMMANDS
# Navigate to project
cd /var/www/html/gebzehisarstore

# MySQL access
sudo mysql -u root -p
# Password: Eticaret2025!
USE okul_kantin;

# Create backup
mysqldump -u root -p okul_kantin > backup.sql

# Git operations
git add .
git commit -m "Description"
git push

# Apache controls
sudo systemctl restart apache2
sudo systemctl status apache2

# View logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# File permissions
sudo chown -R www-data:www-data /var/www/html/gebzehisarstore
sudo chmod -R 755 /var/www/html/gebzehisarstore
sudo chmod -R 777 /var/www/html/gebzehisarstore/uploads

# Firewall status
sudo ufw status

# System monitoring
htop
df -h
free -m

---
