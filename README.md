# Okul Kantini E-Ticaret Sistemi

Okul kantini için geliştirilmiş modern web tabanlı e-ticaret yönetim sistemi. PHP, MySQL ve DaisyUI ile oluşturulmuş dual-panel mimarisi ile admin ve öğrenci panelleri içerir.

## Sunucu Bilgileri

**Production Server:**
- Domain: gebzehisarstore.com
- IP Address: 92.205.110.57
- OS: Ubuntu 22.04.5 LTS
- SSH User: gebzehisarst (sudo privileges)
- SSH Port: 22
- Provider: GoDaddy VPS
- Disk: 39GB total, 3.2GB used
- RAM: 1.9GB total

## Teknik Stack

**Backend:**
- Web Server: Apache 2.4.52 (Ports 80, 443)
- PHP: 8.1.2-1ubuntu2.22
- Database: MySQL 8.0.43-0ubuntu0.22.04.2
- SSL: GoDaddy Certificate (Valid until Aug 31, 2026)

**Frontend:**
- Framework: DaisyUI 4.12.2 + TailwindCSS
- Font: Plus Jakarta Sans (Google Fonts)
- Theme: Dark navy sidebar (#1e293b) with light content areas
- Mobile-first responsive design

**Security:**
- UFW Firewall: Active (22/tcp, 80/tcp, 443/tcp)
- Fail2Ban: SSH protection (maxretry=3, bantime=3600)
- ModSecurity: Web Application Firewall enabled
- ModEvasive: DDoS Protection enabled
- HTTPS: Forced redirect with HSTS headers
- Session timeout: 30 minutes
- Login rate limiting: Max 5 attempts per 15 minutes

## Database Configuration

**MySQL Credentials:**
- Database: okul_kantin
- Root Password: Eticaret2025!
- Web User: webadmin
- Web Password: Web@Admin2025!
- Character Set: utf8mb4_unicode_ci
- Engine: InnoDB

**Database Schema:**
- users (Authentication and role management)
- students (Student information and profiles)
- parents (Parent/guardian information)
- products (Product catalog with pricing and stock)
- product_images (Multi-image support for products)
- orders (Order management and tracking)
- order_items (Order line items)
- settings (System configuration)

## Authentication System

**Login Method:**
- Phone number (10 digits) + PIN (6 digits)
- Password encryption: bcrypt (PASSWORD_DEFAULT)
- Dual-panel access control
- Session regeneration on login
- Auto-logout after 30 minutes of inactivity

**Demo Accounts:**
- Admin: 5551234567 / 123456
- Student: 5559876543 / 123456

## Project Structure
/var/www/html/gebzehisarstore/
├── admin/
│   ├── dashboard.php (Admin statistics and overview)
│   ├── login.php (Admin authentication)
│   ├── profile.php (Admin profile management)
│   ├── products.php (Product listing and management)
│   ├── product-add.php (Add new products with images)
│   ├── students.php (Student management - PENDING)
│   └── orders.php (Order management - PENDING)
├── student/
│   ├── dashboard.php (Product catalog view)
│   ├── profile.php (Student profile with photo upload)
│   ├── product.php (Product detail with image slider)
│   ├── cart.php (Shopping cart - PENDING)
│   └── orders.php (Order history - PENDING)
├── config/
│   └── database.php (PDO database connection)
├── uploads/ (chmod 777)
│   ├── products/ (Product images, max 20 per product)
│   └── profiles/ (Profile photos)
├── login.php (Student authentication page)
├── logout.php (Session termination)
├── .htaccess (Apache configuration)
├── .gitignore (Version control exclusions)
└── README.md (Project documentation)
## Completed Features

**Authentication & Security:**
- Secure phone + PIN login system
- Role-based access control (Admin/Student)
- Session management with timeout
- Password hashing with bcrypt
- Login attempt limiting
- HTTPS enforcement

**Admin Panel:**
- Modern dashboard with statistics
- Product management (CRUD operations)
- Multi-image product uploads (up to 20 images)
- Profile management and password change
- Responsive sidebar navigation
- Real-time statistics display

**Student Panel:**
- Product catalog with card-based layout
- Product detail pages with image slider
- Profile management with photo upload
- Modern responsive interface
- Shopping preparation (cart structure ready)

**Database Integration:**
- Full relational database structure
- Prepared statements for security
- Image management system
- Order tracking architecture
- User profile system

## Pending Features

**E-commerce Functionality:**
- Shopping cart system
- Order placement and tracking
- Payment integration (Credit card, EFT)
- Inventory management
- Order status updates

**Admin Tools:**
- Student registration and management
- Parent information management
- Order processing dashboard
- Comprehensive reporting
- System settings configuration

**Communication:**
- SMS notifications (NetGSM integration)
- Email notifications
- Order status updates
- Low stock alerts

**Advanced Features:**
- Search and filtering
- Product categories
- Bulk operations
- Data export/import
- Advanced analytics

## Installation & Deployment

**Server Requirements:**
- Ubuntu 22.04+ or similar Linux distribution
- Apache 2.4+ with mod_rewrite, mod_ssl
- PHP 8.1+ with extensions: curl, gd, mbstring, mysqli, pdo_mysql
- MySQL 8.0+ or MariaDB 10.6+
- SSL certificate (Let's Encrypt or commercial)

**Security Configuration:**
- UFW firewall configured
- Fail2Ban for SSH protection
- ModSecurity for web application security
- Regular security updates
- Secure file permissions (uploads: 777, others: 755)

**File Upload Limits:**
- Products: 20 images maximum, 5MB per file
- Profiles: 1 image, 5MB maximum
- Supported formats: JPG, JPEG, PNG, GIF

## API Endpoints (Planned)

**Authentication:**
- POST /api/login - User authentication
- POST /api/logout - Session termination

**Products:**
- GET /api/products - Product listing
- GET /api/products/:id - Product details
- POST /api/products - Add product (Admin only)

**Orders:**
- GET /api/orders - Order history
- POST /api/orders - Place new order
- PUT /api/orders/:id - Update order status

## Development Guidelines

**Code Standards:**
- PSR-12 coding standards for PHP
- Prepared statements for all database queries
- CSRF protection on all forms
- Input validation and sanitization
- Error logging and handling

**Security Best Practices:**
- Never store passwords in plain text
- Validate all user inputs
- Use parameterized queries
- Implement proper session management
- Regular security audits

**Performance Considerations:**
- Database query optimization
- Image compression for uploads
- Caching strategies for static content
- Lazy loading for large datasets

## Monitoring & Maintenance

**Log Files:**
- Apache: /var/log/apache2/error.log
- Apache Access: /var/log/apache2/access.log
- PHP Errors: /var/log/php/error.log
- MySQL: /var/log/mysql/error.log

**Backup Strategy:**
- Daily database backups
- Weekly full system backups
- File upload backups
- Configuration file versioning

**System Monitoring:**
- Disk usage monitoring
- Memory usage tracking
- Database performance monitoring
- SSL certificate expiration alerts

## Quick Commands

**Database Operations:**
```bash
# Access MySQL
mysql -u root -p
# Password: Eticaret2025!

# Backup database
mysqldump -u root -p okul_kantin > backup_$(date +%Y%m%d).sql

# Restore database
mysql -u root -p okul_kantin < backup_file.sql
