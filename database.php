<?php
/**
 * Database Configuration
 * Sans Digital Work - SDW
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'sans_digital_db');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Application settings
define('SITE_NAME', 'Sans Digital Work');
define('SITE_URL', 'http://localhost/SANSDigitalWork'); // Change to HTTPS in production
define('ADMIN_EMAIL', 'admin@sansdigitalwork.com');

// Upload settings
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/aadhar/');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Payment gateway settings (Razorpay example)
define('RAZORPAY_KEY_ID', 'YOUR_TEST_KEY_ID'); // Get from Razorpay dashboard
define('RAZORPAY_KEY_SECRET', 'YOUR_TEST_KEY_SECRET');
define('PAYMENT_MODE', 'test'); // 'test' or 'live'

// Security settings
define('ENCRYPTION_KEY', 'CHANGE_THIS_TO_A_SECURE_RANDOM_KEY_32CHARS');
define('SESSION_LIFETIME', 3600); // 1 hour

?>
