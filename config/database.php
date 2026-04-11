<?php
/**
 * Database Connection Class
 * Smart switching between Localhost and Railway/Production
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $charset = "utf8mb4";
    public $conn;
    private static $instance = null;

    public function __construct() {
        // Railway Environment Variables check karega, agar nahi mile toh local settings use karega
        if (getenv('MYSQLHOST')) {
            // Production / Railway Settings
            $this->host     = getenv('MYSQLHOST');
            $this->db_name  = getenv('MYSQLDATABASE');
            $this->username = getenv('MYSQLUSER');
            $this->password = getenv('MYSQLPASSWORD');
            $this->port     = getenv('MYSQLPORT');
        } else {
            // Localhost / XAMPP Settings
            $this->host     = "localhost";
            $this->db_name  = "sans_digital_works";
            $this->username = "root";
            $this->password = ""; // Default XAMPP password is empty
            $this->port     = "3306";
        }
    }

    // Singleton Pattern to prevent multiple connections
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;

        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Professional error message for users
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. SDW team is working on it.'
            ]));
        }
    }

    /**
     * Fetch multiple records
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get single record
     */
    public function single($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Single Query Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert, Update, Delete
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $this->conn->lastInsertId() ?: $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Execute Error: " . $e->getMessage());
            return false;
        }
    }

    // Transactions for sensitive data (Payments/Registration)
    public function beginTransaction() { return $this->getConnection()->beginTransaction(); }
    public function commit() { return $this->getConnection()->commit(); }
    public function rollback() { return $this->getConnection()->rollBack(); }
}

/**
 * Global helper function to get database instance
 */
function getDB() {
    $database = Database::getInstance();
    $database->getConnection();
    return $database;
}
?>
