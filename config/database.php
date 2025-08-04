<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'buyunic_enrollment';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8; SET sql_mode = '';"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'buyunic_enrollment');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('BASE_URL', 'http://localhost/buyunic-enrollment/');
define('ADMIN_EMAIL', 'admin@buyunic.ug');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 1048576); // 1MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'docx']);

// Session configuration
define('SESSION_TIMEOUT', 900); // 15 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 1800); // 30 minutes

// Email configuration (using PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@buyunic.ug');
define('FROM_NAME', 'BUYUNIC Enrollment System');

// Security keys
define('ENCRYPTION_KEY', 'your-32-character-secret-key-here');
define('JWT_SECRET', 'your-jwt-secret-key-here');

// Payment gateway configurations
define('PESAPAL_CONSUMER_KEY', 'your-pesapal-consumer-key');
define('PESAPAL_CONSUMER_SECRET', 'your-pesapal-consumer-secret');

// MTN Mobile Money Configuration
define('MTN_MOBILE_MONEY_DIAL', '*165*3#');
define('MTN_MOBILE_MONEY_MERCHANT_ID', '693183');
define('MTN_MOBILE_MONEY_MERCHANT_NAME', 'BUYUNIC Training Center');

// Additional payment gateway configurations (for future integration)
define('MOBILE_MONEY_API_KEY', 'your_mobile_money_api_key');
define('FLEXIPAY_API_KEY', 'your_flexipay_api_key');

// Application specific settings
define('OTP_EXPIRY', 300); // 5 minutes
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
define('APPLICATION_EDIT_LIMIT', 3);
define('REGISTRATION_FEE', 30000);

// Create database connection function
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET sql_mode = '';"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Initialize database connection
$pdo = getDBConnection();
?>