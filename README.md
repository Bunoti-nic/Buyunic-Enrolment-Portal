# BUYUNIC Enrollment Portal

A comprehensive web-based enrollment system for BUYUNIC IT training programs, built with PHP, MySQL, HTML, CSS, and JavaScript.

## 🌟 Features

### 🔐 Authentication System
- **Multi-step Registration** with email verification
- **Secure Login** with brute force protection
- **Password Reset** via secure token-based email flow
- **Multi-Factor Authentication** for admin users
- **Session Management** with automatic timeout

### 👤 User Dashboard
- **Personalized Dashboard** with progress tracking
- **Multi-step Application Form** with autosave functionality
- **Document Upload Center** (up to 10 files, 1MB each)
- **Real-time Notifications** and messaging system
- **PDF Generation** for summaries and certificates
- **Payment Integration** with multiple gateways

### 📝 Application Management
- **Personal Details** collection
- **Academic Background** tracking
- **Internee Information** management
- **Program Selection** from training catalog
- **Document Verification** system
- **Progress Tracking** with completion percentage

### 💳 Payment Processing
- **Multiple Payment Methods**: Mobile Money, FlexiPay, Pesapal
- **Unique Application IDs** (A-I-DDMMYY-XXXXXX format)
- **Real-time Payment Status** updates
- **Receipt Generation** with PDF download
- **Payment Verification** system

### 🛠 Admin Panel
- **MFA-Protected Access** for administrators
- **Application Management** with filtering and approval workflows
- **Document Review System** with approval/rejection
- **Payment Verification Tools**
- **Analytics Dashboard** with visual reports
- **User Management** with role-based access
- **Audit Logging** with IP tracking

### 📊 Training Catalog
Complete course management for:
- **Microsoft Office Suite** (Word, Excel, PowerPoint, etc.)
- **Graphics & Web Development** (Photoshop, HTML, WordPress, etc.)
- **Specialized IT Programs** (Networking, CCTV, Accounting Software, etc.)
- **Internship & Research** programs

### 🔒 Security Features
- **CSRF Protection** on all forms
- **Input Sanitization** and validation
- **Session Security** with timeout management
- **File Upload Validation** with type restrictions
- **QR Code Verification** for certificates
- **Audit Logging** for admin actions

## 🚀 Installation

### System Requirements
- **PHP 7.4+** with extensions: PDO, PDO_MySQL, mbstring, openssl, curl, gd
- **MySQL 5.7+** or MariaDB 10.2+
- **Apache/Nginx** web server
- **Composer** for dependency management

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/buyunic/enrollment-portal.git
   cd enrollment-portal
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```
   
   **Note**: If Composer is not installed or this step fails, don't worry! The portal includes a fallback email system that works without PHPMailer. See `docs/composer_setup.md` for detailed setup instructions.

3. **Configure database**
   - Edit `config/database.php` with your database credentials
   - Update SMTP settings for email functionality

4. **Set permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 config/database.php
   ```

5. **Run setup**
   - Navigate to `http://your-domain/setup.php`
   - Follow the setup wizard
   - Delete `setup.php` after completion

### Default Credentials
- **Admin**: admin@buyunic.ug / password
- **Change immediately after setup!**

### Payment Configuration

#### MTN Mobile Money Setup
- **Dial Code**: `*165*3#`
- **Merchant ID**: `693183`
- **Merchant Name**: BUYUNIC Training Center

#### For Students - Payment Instructions
1. Dial `*165*3#` on your MTN phone
2. Select "Pay Merchant" option
3. Enter Merchant ID: `693183`
4. Enter the required amount
5. Complete payment with your MTN Mobile Money PIN
6. Save the transaction reference number
7. Upload payment proof in your application portal

#### Payment Amounts
- **Non-Internees**: UGX 30,000 (Registration Fee)
- **Internees**: FREE (No registration fee)
- **Course Fees**: As listed in the training program catalog

See `docs/payment_instructions.md` for detailed payment guide.

## 🗂 File Structure

```
buyunic-enrollment/
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── verify.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   └── logout.php
├── user/
│   ├── dashboard.php
│   ├── application.php
│   ├── upload.php
│   ├── payment.php
│   └── messages.php
├── admin/
│   ├── dashboard.php
│   ├── applications.php
│   ├── payments.php
│   ├── users.php
│   ├── reports.php
│   └── logs.php
├── classes/
│   ├── Email.php
│   ├── PDF.php
│   └── Payment.php
├── includes/
│   └── functions.php
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── uploads/
├── vendor/
├── index.php
├── setup.php
├── composer.json
└── README.md
```

## ⚙ Configuration

### Database Settings
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'buyunic_enrollment');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Email Configuration
```php
// SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

### Payment Gateway Setup
```php
// Pesapal Configuration
define('PESAPAL_CONSUMER_KEY', 'your-key');
define('PESAPAL_CONSUMER_SECRET', 'your-secret');
```

## 🎓 Training Programs

### Microsoft Office Suite
- General intro + MS Word (120,000 UGX - 3 weeks)
- PowerPoint (50,000 UGX - 2 weeks)
- Excel (60,000 UGX - 2 weeks)
- Access/Database (60,000 UGX - 2 weeks)
- Publisher (50,000 UGX - 2 weeks)
- Internet Basics (50,000 UGX - 2 weeks)

### Graphics & Web Development
- Adobe Photoshop (550,000 UGX - 4 weeks)
- Adobe Illustrator (550,000 UGX - 4 weeks)
- HTML Language (600,000 UGX - 4 weeks)
- WordPress CMS (500,000 UGX - 4 weeks)
- CorelDRAW (550,000 UGX - 4 weeks)

### Specialized IT Programs
- Computer Networking (550,000 UGX - 6 weeks)
- Hardware/Software Troubleshooting (300,000 UGX - 3 weeks)
- CCTV Installation & Config (550,000 UGX - 3 weeks)
- Accounting Software (Tally, QuickBooks) (550,000 UGX - 6 weeks)
- Data Analysis (SPSS, Stata, Epi Data) (350,000-550,000 UGX - 6 weeks)

### Internship & Research
- Custom IT Project/Research-Based Training (400,000 UGX - 2 months)
- Registration Fee: 30,000 UGX (Waived for internees)

## 🔧 API Endpoints

### Authentication
- `POST /auth/register.php` - User registration
- `POST /auth/login.php` - User login
- `POST /auth/forgot_password.php` - Password reset request
- `GET /auth/verify.php?token=` - Email verification

### User Dashboard
- `GET /user/dashboard.php` - User dashboard
- `POST /user/application.php` - Submit/update application
- `POST /user/upload.php` - File upload
- `POST /user/payment.php` - Payment processing

### Admin Panel
- `GET /admin/dashboard.php` - Admin dashboard
- `GET /admin/applications.php` - Application management
- `POST /admin/approve.php` - Approve/reject applications
- `GET /admin/reports.php` - Generate reports

## 🛡 Security

### Implemented Security Measures
- **Password Hashing**: Argon2ID with secure parameters
- **CSRF Protection**: Tokens on all forms
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization and output encoding
- **File Upload Security**: Type validation and path restrictions
- **Session Security**: Timeout and regeneration
- **Brute Force Protection**: Account lockout after failed attempts
- **Email Verification**: Required for account activation

### Security Recommendations
1. Enable HTTPS in production
2. Regular security updates
3. Database backups
4. Monitor admin logs
5. Implement rate limiting
6. Use strong passwords
7. Regular security audits

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
```bash
# Check MySQL service
sudo systemctl status mysql
# Verify credentials in config/database.php
```

**Email Not Sending**
```bash
# Check SMTP settings
# Verify firewall allows SMTP port
# Check email provider security settings
```

**File Upload Issues**
```bash
# Check directory permissions
chmod 755 uploads/
# Verify PHP upload settings
php -i | grep upload
```

**Session Issues**
```bash
# Check PHP session configuration
# Verify session directory permissions
# Clear browser cookies
```

## 📞 Support

### Contact Information
- **Location**: Plot 28, North Road, Northern City Division, Mbale City
- **Phone**: +256 207 901 434
- **Email**: info@buyunic.ug / apply@buyunic.ug
- **Website**: https://buyunic.ug

### Getting Help
1. Check this README first
2. Review system logs in `/var/log/`
3. Check admin logs in the admin panel
4. Contact support with detailed error information

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **BUYUNIC Team** for requirements and testing
- **PHP Community** for excellent documentation
- **Bootstrap/CSS** framework for responsive design
- **Font Awesome** for icons

---

**BUYUNIC Enrollment Portal v1.0**  
*Empowering the next generation of IT professionals*